<?php

namespace BitApps\Assist\HTTP\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

use AllowDynamicProperties;
use BitApps\Assist\Config;
use BitApps\Assist\Deps\BitApps\WPKit\Hooks\Hooks;
use BitApps\Assist\Deps\BitApps\WPKit\Http\Request\Request;
use BitApps\Assist\Model\WidgetChannel;
use WP_Query;

#[AllowDynamicProperties]
final class WpSearchController
{
    /**
     * Channel name stored for the WP Search channel.
     */
    private const CHANNEL_NAME = 'WP-Search';

    /**
     * Post types the channel falls back to when an admin saves the
     * channel without touching the post type checkboxes.
     *
     * @var string[]
     */
    private const DEFAULT_POST_TYPES = ['post', 'page'];

    public function wpSearch(Request $request)
    {
        $validated = $request->validate([
            'search'      => ['string', 'sanitize:text'],
            'page'        => ['integer'],
            'postTypes.*' => ['nullable', 'string', 'sanitize:text'],
        ]);

        $requestedTypes = isset($validated['postTypes']) ? (array) $validated['postTypes'] : self::DEFAULT_POST_TYPES;

        // Never trust the caller: the widget config decides which post types
        // are searchable, not the request body.
        $postTypes = array_intersect($requestedTypes, $this->getAllowedPostTypes());

        if (empty($postTypes)) {
            return ['data' => [], 'pagination' => $this->getEmptyPagination()];
        }

        return $this->getPageAndPosts(
            $validated['search'] ?? '',
            $validated['page'] ?? 1,
            array_values($postTypes)
        );
    }

    /**
     * Post types an anonymous caller is allowed to search.
     *
     * Union of the post types enabled on active WP Search channels, capped by
     * the post types WordPress itself exposes publicly, so unconfigured or
     * non-public post types can never be enumerated through this endpoint.
     *
     * @return string[]
     */
    private function getAllowedPostTypes()
    {
        $channels = WidgetChannel::where('status', 1)
            ->where('channel_name', self::CHANNEL_NAME)
            ->get(['config']);

        $configured = [];

        if (\is_array($channels)) {
            foreach ($channels as $channel) {
                $channelTypes = isset($channel->config->wp_post_types)
                    ? (array) $channel->config->wp_post_types
                    : self::DEFAULT_POST_TYPES;

                $configured = array_merge($configured, $channelTypes);
            }
        }

        $allowed = [];

        foreach (array_unique($configured) as $postType) {
            $postTypeObject = get_post_type_object($postType);

            // Drop only what is positively known to be non-public. A type the
            // admin picked that is not registered on front end requests stays,
            // so this cannot silently break an existing configuration.
            if (\is_null($postTypeObject) || !empty($postTypeObject->public)) {
                $allowed[] = $postType;
            }
        }

        return array_filter((array) Hooks::applyFilter(Config::withPrefix('wp_search_allowed_post_types'), $allowed), 'is_string');
    }

    private function getPageAndPosts($search, $page, $postTypes)
    {
        $paged = max(1, \intval($page));
        $search = trim($search);

        $queryArgs = [
            'post_type'              => $postTypes,
            'post_status'            => 'publish',
            'posts_per_page'         => 10,
            'orderby'                => 'relevance',
            'paged'                  => $paged,
            'no_found_rows'          => false, // keep pagination totals
            'has_password'           => false, // exclude password-protected posts
            'update_post_meta_cache' => false, // performance flag
            'update_post_term_cache' => false, // performance flag
            'ignore_sticky_posts'    => true,  // performance/consistency flag
        ];

        if (!empty($search)) {
            $queryArgs['s'] = $search;
            $queryArgs['search_columns'] = ['post_title'];
        }

        $query = new WP_Query($queryArgs);

        if (is_wp_error($query)) {
            return ['data' => [], 'pagination' => $this->getEmptyPagination()];
        }

        return [
            'data'       => $this->processPosts($query->posts),
            'pagination' => $this->buildPagination($query, $paged)
        ];
    }

    private function processPosts($posts)
    {
        return array_map(function ($post) {
            return [
                'post_link'  => get_permalink($post->ID),
                'post_title' => $post->post_title,
                'post_type'  => $post->post_type,
            ];
        }, array_filter($posts, function ($post) {
            return $post && \is_object($post);
        }));
    }

    private function buildPagination($query, $currentPage)
    {
        $maxPages = $query->max_num_pages;

        if ($maxPages <= 0) {
            return $this->getEmptyPagination();
        }

        $nextPage = $currentPage + 1;
        $previousPage = $currentPage - 1;

        return [
            'total'        => $maxPages,
            'current'      => $currentPage,
            'next'         => $nextPage,
            'previous'     => $previousPage,
            'has_next'     => $nextPage <= $maxPages,
            'has_previous' => $previousPage >= 1,
            'total_posts'  => $query->found_posts,
        ];
    }

    private function getEmptyPagination()
    {
        return [
            'total'        => 0,
            'current'      => 1,
            'next'         => null,
            'previous'     => null,
            'has_next'     => false,
            'has_previous' => false,
            'total_posts'  => 0,
        ];
    }
}
