const argEnvIndex = process.argv.indexOf('--env')
let argEnv = (argEnvIndex !== -1 && process.argv[argEnvIndex + 1]) || ''

const RUN_ENV_MAP = {
  local: {
    instances: 1,
    max_memory_restart: '250M'
  },
  staging: {
    instances: 3,
    max_memory_restart: '250M'
  },
  production: {
    instances: 5,
    max_memory_restart: '1024M'
  }
}

if (!(argEnv in RUN_ENV_MAP)) {
  argEnv = 'prod'
}

module.exports = {
  apps: [
    {
      name: 'ABTesting',
      exec_mode: 'cluster',
      instances: RUN_ENV_MAP[argEnv].instances,
      script: 'node_modules/next/dist/bin/next',
      args: 'start',
      autorestart: true,
      max_memory_restart: RUN_ENV_MAP[argEnv].max_memory_restart,
      env_local: {
        APP_ENV: 'local',
      },
      env_staging: {
        APP_ENV: 'staging',
      },
      env_production: {
        APP_ENV: 'production',
      }
    }
  ]
}