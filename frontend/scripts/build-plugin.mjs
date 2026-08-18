#!/usr/bin/env node

/* eslint-disable no-console */
import { execSync } from 'node:child_process'
import path from 'node:path'
import { exit } from 'node:process'
import { fileURLToPath } from 'node:url'
import { program } from 'commander'

import fse from 'fs-extra'

const run = (command, options = {}) => execSync(command, { stdio: 'inherit', ...options })

const commandExistsSync = (command) => {
  try {
    execSync(command, { stdio: 'ignore' })
    return true
  } catch {
    return false
  }
}

program
  .name('build-plugin')
  .description('Build plugin package (without pro)')
  .option('-o, --outdir <char>', 'specify output directory', 'build')
  .option('-z, --zip', 'generate zip file', false)
  .option('-c, --cleanbuild', 'delete staging directory after zip', false)
  .option('-n, --nobuild', 'skip frontend build', false)
  .option('-i, --noi18n', 'skip i18n generation', false)
  .requiredOption('-s, --slug <char>', 'specify plugin slug')
  .parse()

const {
  outdir,
  slug: pluginSlug,
  zip,
  cleanbuild,
  nobuild,
  noi18n,
} = program.opts()

const frontendDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const rootDir = path.resolve(frontendDir, '..')
const outputDirectory = path.resolve(rootDir, outdir, pluginSlug)

const readPluginVersion = () => {
  try {
    const header = fse.readFileSync(path.resolve(rootDir, 'index.php'), 'utf8')
    return header.match(/Version:\s*([\d.]+)/)?.[1] ?? ''
  } catch {
    return ''
  }
}

const pluginVersion = readPluginVersion()
const zipName = pluginVersion ? `${pluginSlug}-${pluginVersion}.zip` : `${pluginSlug}.zip`
const outputZip = path.resolve(rootDir, outdir, zipName)

const filesAndFolders = [
  'assets',
  'backend',
  'iframe',
  'img',
  'vendor',
  'index.php',
  'readme.txt',
  'composer.json',
]

console.log('options passed:', {
  outdir,
  pluginSlug,
  pluginVersion,
  zip,
  outputDirectory,
  cleanbuild,
  nobuild,
  noi18n,
})

if (
  !commandExistsSync('composer --version')
  || !commandExistsSync('php --version')
  || (zip && !commandExistsSync('zip --version'))
) {
  console.error('Missing required command(s): composer/php/zip')
  exit(1)
}

if (!nobuild) {
  run('pnpm run pda', { cwd: frontendDir })
}

if (!noi18n) {
  try {
    run('pnpm run i18n', { cwd: frontendDir })
  }
  catch {
    console.warn('Warning: i18n generation failed (skipping)')
  }
}

run('composer install --no-dev --optimize-autoloader', { cwd: rootDir })

await Promise.all([fse.emptyDir(outputDirectory), fse.remove(outputZip)]).catch((error) => {
  console.error(error)
  exit(1)
})

for (const item of filesAndFolders) {
  const sourcePath = path.resolve(rootDir, item)
  const destinationPath = path.resolve(outputDirectory, item)
  await fse.copy(sourcePath, destinationPath)
}

if (zip)
  run(`zip -rq "${outputZip}" "${pluginSlug}"`, { cwd: path.resolve(rootDir, outdir) })

await fse.remove(path.resolve(rootDir, 'vendor'))
run('composer install', { cwd: rootDir })

if (cleanbuild)
  await fse.remove(outputDirectory)

console.log(`Done: ${zip ? outputZip : outputDirectory}`)
