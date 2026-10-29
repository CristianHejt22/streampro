import { spawnSync } from 'node:child_process'

const env = {
  ...process.env,
  RAYON_NUM_THREADS: '1'
}

const result = spawnSync(
  process.execPath,
  ['--max-old-space-size=2048', './node_modules/vite/bin/vite.js', 'build'],
  { stdio: 'inherit', env }
)

process.exit(result.status ?? 0)
