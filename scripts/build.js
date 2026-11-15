import { spawnSync } from 'node:child_process'
import path from 'node:path'
import fs from 'node:fs'

const localTemp = path.resolve('node_modules/.cache/temp')
try {
  fs.mkdirSync(localTemp, { recursive: true })
} catch {}

const env = {
  ...process.env,
  TMPDIR: localTemp,
  TEMP: localTemp,
  TMP: localTemp,
  RAYON_NUM_THREADS: '1'
}

const result = spawnSync(
  process.execPath,
  ['--max-old-space-size=2048', './node_modules/vite/bin/vite.js', 'build'],
  { stdio: 'inherit', env }
)

process.exit(result.status ?? 0)
