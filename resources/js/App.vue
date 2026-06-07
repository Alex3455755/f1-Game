<template>
  <div class="app-wrapper">
    <header class="app-header">
      <div class="header-inner">
        <div class="logo">
          <span class="logo-f1">F1</span>
          <span class="logo-text">Engineer Simulator</span>
        </div>
        <nav v-if="store.season" class="nav-links">
          <router-link :to="`/season/${store.season.id}`">Сезон</router-link>
          <router-link :to="`/season/${store.season.id}/standings`">Очки</router-link>
        </nav>
        <div v-if="store.season" class="team-badge" :style="{ borderColor: store.season.player_team?.color }">
          {{ store.season.player_team?.short_name }}
        </div>
      </div>
    </header>
    <main class="app-main">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useGameStore } from './stores/gameStore';

const store = useGameStore();
onMounted(() => store.loadCurrentSeason());
</script>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: #0a0a0f;
  color: #e8e8e8;
  font-family: 'Segoe UI', system-ui, sans-serif;
  min-height: 100vh;
}

.app-wrapper { display: flex; flex-direction: column; min-height: 100vh; }

.app-header {
  background: linear-gradient(90deg, #0d0d14 0%, #1a1a2e 100%);
  border-bottom: 2px solid #e10600;
  padding: 0 24px;
  height: 56px;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-inner {
  max-width: 1400px;
  margin: 0 auto;
  height: 100%;
  display: flex;
  align-items: center;
  gap: 24px;
}

.logo { display: flex; align-items: center; gap: 10px; }
.logo-f1 {
  background: #e10600;
  color: white;
  font-weight: 900;
  font-size: 20px;
  padding: 2px 8px;
  border-radius: 4px;
  letter-spacing: 1px;
}
.logo-text { font-size: 16px; font-weight: 600; color: #ccc; }

.nav-links { display: flex; gap: 20px; margin-left: auto; }
.nav-links a {
  color: #aaa;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  padding: 4px 0;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}
.nav-links a:hover, .nav-links a.router-link-active {
  color: #fff;
  border-bottom-color: #e10600;
}

.team-badge {
  background: rgba(255,255,255,0.05);
  border: 2px solid #444;
  border-radius: 6px;
  padding: 4px 12px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 1px;
}

.app-main {
  flex: 1;
  max-width: 1400px;
  margin: 0 auto;
  width: 100%;
  padding: 24px;
}

/* Global utility classes */
.card {
  background: #13131f;
  border: 1px solid #2a2a3e;
  border-radius: 12px;
  padding: 20px;
}
.btn {
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary { background: #e10600; color: white; }
.btn-primary:hover { background: #c00500; transform: translateY(-1px); }
.btn-secondary { background: #2a2a3e; color: #ccc; }
.btn-secondary:hover { background: #353552; }
.btn-success { background: #16a34a; color: white; }
.btn-success:hover { background: #15803d; }
.btn-warning { background: #d97706; color: white; }
.btn-warning:hover { background: #b45309; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.section-title {
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.section-title::before {
  content: '';
  display: block;
  width: 4px;
  height: 24px;
  background: #e10600;
  border-radius: 2px;
}

.tire-soft { color: #f43f5e; font-weight: 700; }
.tire-medium { color: #fbbf24; font-weight: 700; }
.tire-hard { color: #e8e8e8; font-weight: 700; }
.tire-intermediate { color: #34d399; font-weight: 700; }
.tire-wet { color: #60a5fa; font-weight: 700; }

.weather-sunny { color: #fbbf24; }
.weather-cloudy { color: #94a3b8; }
.weather-rain { color: #60a5fa; }
.weather-heavy_rain { color: #818cf8; }
</style>
