# M3U Stream Player

A self-hosted web M3U/IPTV player with a built-in **PHP streaming proxy** to bypass CORS restrictions.

## Features
- Load playlists via URL, file upload, or paste
- HLS (m3u8) support via hls.js
- Built-in proxy for CORS-restricted streams
- Channel search + group filtering
- Keyboard shortcuts (↑↓ navigate, Space play/pause, M mute, F fullscreen)
- No database needed — runs entirely in PHP + Apache

---

## Quick Start

### Docker Compose (recommended)
```bash
git clone <your-repo>
cd m3u-player
docker compose up -d
```
Open: http://localhost:8080

### Docker only
```bash
docker build -t m3u-player .
docker run -d -p 8080:80 --name m3u-player m3u-player
```
Open: http://localhost:8080

---

## Deploy on Free Platforms

### Render.com (free tier)
1. Push this repo to GitHub
2. Go to https://render.com → New → Web Service
3. Connect your repo
4. Set:
   - **Runtime**: Docker
   - **Port**: 80
5. Deploy — you get a free HTTPS URL

### Railway.app (free tier)
1. Push to GitHub
2. New Project → Deploy from GitHub repo
3. Railway auto-detects the Dockerfile
4. Set port to `80` in settings

### Fly.io (free tier)
```bash
fly launch   # detects Dockerfile automatically
fly deploy
```

---

## Project Structure
```
m3u-player/
├── Dockerfile
├── docker-compose.yml
├── public/
│   ├── index.php       # Router
│   ├── player.html     # Full UI
│   └── .htaccess       # Apache routing
└── src/
    ├── parser.php      # M3U parser (returns JSON)
    └── proxy.php       # Stream proxy (bypasses CORS)
```

## How the Proxy Works
When **Proxy: ON** (default), stream URLs are fetched server-side by PHP and re-streamed to the browser. This:
- Bypasses CORS restrictions on IPTV streams
- Works on all free Docker hosting platforms
- Supports chunked streaming (no full download)

Toggle **Proxy: OFF** to stream directly (faster, but CORS may block some streams).
