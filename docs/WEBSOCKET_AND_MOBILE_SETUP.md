Symfony WebSocket + React Native (EasySave Mobile)

Summary
- Add WebSocket server for real-time (used by React Native mobile app).
- Nginx is configured to proxy `/ws/` to a websocket upstream.
- Dockerfile is adjusted so single-container runtime (production) points nginx upstreams to localhost to avoid 502/504 on Railway.
- Entrypoint no longer runs migrations by default (prevents long boot time/timeout).

1) Backend (Symfony) — server-side WebSocket (recommended: GosWebSocketBundle + Ratchet)

Steps (Symfony project root):

- Install the bundle:

  composer require gos/web-socket-bundle

- Enable bundle (if using Symfony Flex this is automatic). Otherwise add to `config/bundles.php`:

  Gos\WebSocketBundle\GosWebSocketBundle::class => ['all' => true],

- Add config `config/packages/gos_web_socket.yaml` (example):

  gos_web_socket:
      server:
          port: 8080        # must match the nginx upstream ws-upstream
          host: 0.0.0.0
          router: true
      client:
          session_handler: null

- Create a topic service (example src/WebSocket/Topic/ChatTopic.php) implementing `Gos\WebSocketBundle\Topic\TopicInterface` and set it as a service.

- Run the server locally:

  php bin/console gos:websocket:server --host=0.0.0.0 --port=8080

  In production we add a supervisor program so the websocket server is managed automatically (see `docker/supervisor/supervisord.conf`).

- Broadcast from Symfony controllers or services using the Gos publisher service (see bundle docs).

Notes on supervisord in this repo
- `docker/supervisor/supervisord.conf` now contains a `program:websocket` entry that will start the gos websocket server on port 8080 when the bundle is installed and `bin/console gos:websocket:server` exists.

2) Nginx / Docker adjustments (already applied)

- `nginx-main.conf` now contains an upstream `ws-upstream` (defaults to `websocket:8080`) and a `location /ws/` that proxies websocket upgrades to it.
- Production `Dockerfile` rewrites `server php:9000;` → `server 127.0.0.1:9000;` and `server websocket:8080;` → `server 127.0.0.1:8080;` at build time so a single-container image (php-fpm + nginx + supervisord) works without 502 errors.
- `entrypoint.sh` no longer runs migrations by default to avoid long boot-time operations on ephemeral hosts (set `RUN_MIGRATIONS=1` if you explicitly want migrations to run).

Railway / Deployment notes
- Ensure your Railway environment variables are set as needed (DB_HOST, DB_USER, DB_PASSWORD, APP_ENV, RUN_MIGRATIONS=1 if you want migrations on first deploy).
- If using single-container image on Railway, the Dockerfile rewrite will make nginx target local php-fpm and websocket server.
- When using a multi-container setup (docker-compose locally), the upstreams will still point to `php` and `websocket` service names.

3) Mobile side (EasySaveMobile) — React Native WebSocket and Metro issues

WebSocket client (React Native)
- React Native supports the standard `WebSocket` API. Example usage:

  const ws = new WebSocket('ws://<YOUR_SERVER_HOST>/ws/');
  ws.onopen = () => console.log('open');
  ws.onmessage = (e) => console.log('message', e.data);
  ws.onerror = (e) => console.log('error', e.message);
  ws.onclose = () => console.log('closed');

- If your server requires authentication, send an initial message with a token and use that to associate the session.

Dependencies for mobile
- No special native module required for basic WebSocket usage.
- For reconnection/control helpers, consider `react-native-reconnecting-websocket` or implement reconnect logic.

Metro "Failed to start watch mode" troubleshooting (Windows)

Common causes:
- Watchman not available on Windows (Metro falls back and sometimes errors).
- Too many files/watcher limits or antivirus interfering.
- Running inside Git Bash where Windows path handling breaks.

Quick fixes to try in the EasySaveMobile directory:

1) Use PowerShell or Command Prompt (not Git Bash) and run:

  set WATCHMAN=0
  npx react-native start --reset-cache

2) Disable watchman in Metro and use polling by adding `metro.config.js` at the project root:

  const { getDefaultConfig } = require('metro-config');
  module.exports = (async () => {
    const config = await getDefaultConfig();
    config.watchman = false;
    config.resetCache = true;
    return config;
  })();

  Then start with:

  npx react-native start --reset-cache

3) If still failing, try enabling polling (more CPU but reliable on Windows):

  set CHOKIDAR_USEPOLLING=true
  npx react-native start --reset-cache

4) Ensure the metro server is reachable from your device:
- Start Metro bound to 0.0.0.0: `npx react-native start --host 0.0.0.0 --port 8081`
- On the device, point the app dev server to your machine IP:666 (use in-app dev menu or `adb reverse` / `adb devices` for Android).

Device run issues
- For Android physical device, enable USB debugging and run:

  adb devices
  npx react-native run-android --deviceId <device-id>

- If app cannot connect to Metro, ensure phone and machine on same network or use `adb reverse tcp:8081 tcp:8081`.

5) Building APK (release)

In `EasySaveMobile/android` run (Windows CMD/PowerShell):

  cd android
  gradlew assembleRelease

APK location (default):
- `android/app/build/outputs/apk/release/app-release.apk`

If you want the final artifact in `android/outputs`, run after build:

  mkdir -p android/outputs
  copy android\app\build\outputs\apk\release\app-release.apk android\outputs\

Notes
- Ensure Android SDK, Java JDK, and environment variables (`ANDROID_HOME`, `JAVA_HOME`) are set on your machine.
- For signing, configure `android/gradle.properties` and `keystore` settings as per React Native docs.

If you want, I can:
- Add example `config/packages/gos_web_socket.yaml` and a sample Topic service in this repo.
- Run quick checks or modify more Docker compose entries to include a `websocket` service for local dev.
- Prepare a small script for building and copying the APK inside the EasySaveMobile project (I cannot edit files outside the current workspace unless you copy them here or allow me to operate on that path).


References
- GosWebSocketBundle: https://github.com/GeniusesOfSymfony/WebSocketBundle
- Ratchet: http://socketo.me/
- React Native WebSocket: https://reactnative.dev/docs/network


*** End of document
