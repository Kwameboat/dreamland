/**
 * Dreamland cron runner — prediction resolution + watch pot expiry.
 * Schedule: node workers/cron-worker.js (or use system cron calling php yii dreamland-cron/*)
 */
const { spawn } = require('child_process');
const path = require('path');

const yii = path.join(__dirname, '..', 'yii');

function run(command, args) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, { cwd: path.join(__dirname, '..'), shell: true });
    child.on('close', (code) => (code === 0 ? resolve() : reject(new Error(`${args.join(' ')} exited ${code}`))));
  });
}

async function tick() {
  try {
    await run('php', [yii, 'dreamland-cron/resolve-predictions']);
    await run('php', [yii, 'dreamland-cron/expire-watch-pots']);
    await run('php', [yii, 'dreamland-cron/reset-streaks']);
    console.log('[Dreamland Cron]', new Date().toISOString(), 'completed');
  } catch (err) {
    console.error('[Dreamland Cron Error]', err.message);
  }
}

tick();
setInterval(tick, 15 * 60 * 1000);
