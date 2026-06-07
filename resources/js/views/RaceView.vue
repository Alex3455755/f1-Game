<template>
  <div class="race-view">
    <!-- Race header -->
    <div class="race-header">
      <div class="race-title">
        <div class="page-label">{{ raceStatus === 'completed' ? 'Гонка завершена' : 'Гонка' }}</div>
        <h1>{{ weekendName }}</h1>
      </div>
      <div class="race-meta">
        <div class="lap-counter">
          <span class="lap-current">{{ currentLap }}</span>
          <span class="lap-sep"> / </span>
          <span class="lap-total">{{ totalLaps }}</span>
          <span class="lap-label"> кругов</span>
        </div>
        <div class="weather-badge" :class="`weather-${weather}`">{{ weatherLabels[weather] }}</div>
      </div>
    </div>

    <!-- Init screen -->
    <div v-if="!raceStarted && !loading" class="init-screen card">
      <div class="init-icon">🏎️</div>
      <h2>Гонка готова</h2>
      <p>Пилоты стоят на стартовой решётке. Выбери начальные составы и начни!</p>
      <div class="compound-select">
        <div v-for="driver in playerDrivers" :key="driver.id" class="compound-row">
          <span class="driver-label">{{ driver.name }}</span>
          <div class="compound-btns">
            <button
              v-for="c in ['soft', 'medium', 'hard']"
              :key="c"
              class="compound-btn"
              :class="[`tire-${c}`, { active: startCompounds[driver.id] === c }]"
              @click="startCompounds[driver.id] = c"
            >
              {{ compoundLabels[c] }}
            </button>
          </div>
        </div>
      </div>
      <button class="btn btn-primary btn-large" @click="initRace">
        🚦 Старт гонки
      </button>
    </div>

    <div v-if="loading" class="loading-overlay">
      <div class="sim-spinner"></div>
      <div>{{ loadingText }}</div>
    </div>

    <!-- Race in progress -->
    <div v-if="raceStarted" class="race-layout">
      <!-- Live timing -->
      <div class="timing-panel card">
        <div class="timing-header">
          <div class="section-title" style="margin-bottom:0">Live Timing</div>
          <div class="lap-pill">Круг {{ currentLap }}/{{ totalLaps }}</div>
        </div>
        <div class="timing-list">
          <div
            v-for="(r, i) in sortedResults"
            :key="r.id"
            class="timing-row"
            :class="{ player: isPlayerDriver(r.driver_id), dnf: r.status === 'dnf' }"
          >
            <div class="t-pos" :class="posClass(i + 1)">{{ i + 1 }}</div>
            <div class="t-team-bar" :style="{ background: r.driver?.team?.color }"></div>
            <div class="t-driver">
              <span class="t-code">{{ r.driver?.code }}</span>
            </div>
            <div class="t-tire" :class="`tire-${r.tire_compound}`">
              {{ compoundIcons[r.tire_compound] }}
            </div>
            <div class="t-gap">
              <span v-if="i === 0" class="t-leader">ЛИДЕР</span>
              <span v-else class="t-gap-val">+{{ Number(r.gap_to_leader).toFixed(1) }}s</span>
            </div>
            <div class="t-pits">
              <span class="pit-count">{{ r.pit_stop_count }}п</span>
            </div>
            <div v-if="r.status === 'dnf'" class="t-dnf">DNF</div>
          </div>
        </div>
      </div>

      <!-- Controls panel -->
      <div class="controls-panel">
        <!-- Player driver controls -->
        <div v-for="driver in playerDrivers" :key="driver.id" class="driver-control card">
          <div class="dc-header">
            <div class="dc-driver-name">
              <span class="dc-number">{{ driver.number }}</span>
              {{ driver.name }}
            </div>
            <div class="dc-pos">
              P{{ getDriverPosition(driver.id) }}
            </div>
          </div>

          <div class="dc-status">
            <div class="dc-tire" :class="`tire-${getDriverResult(driver.id)?.tire_compound}`">
              {{ compoundLabels[getDriverResult(driver.id)?.tire_compound] || '—' }}
            </div>
            <div class="dc-pits">{{ getDriverResult(driver.id)?.pit_stop_count || 0 }} пит-стопов</div>
            <div class="dc-gap">
              +{{ Number(getDriverResult(driver.id)?.gap_to_leader || 0).toFixed(1) }}s от лидера
            </div>
          </div>

          <!-- Radio messages -->
          <div class="radio-section">
            <div class="radio-label">📻 Радио</div>
            <div class="radio-btns">
              <button
                v-for="cmd in radioCommands"
                :key="cmd.type"
                class="radio-btn"
                :class="`radio-${cmd.color}`"
                @click="sendRadio(driver.id, cmd.type)"
              >
                {{ cmd.label }}
              </button>
            </div>
          </div>

          <!-- Pit stop -->
          <div class="pit-section">
            <div class="pit-label">🛞 Пит-стоп</div>
            <div class="pit-btns">
              <button
                v-for="c in availableCompounds"
                :key="c"
                class="pit-btn"
                :class="`tire-${c}`"
                @click="callPitStop(driver.id, c)"
                :disabled="raceStatus === 'completed'"
              >
                {{ compoundLabels[c] }}
              </button>
            </div>
          </div>
        </div>

        <!-- Radio feed -->
        <div class="radio-feed card">
          <div class="section-title">Переговоры</div>
          <div class="radio-messages" ref="radioFeed">
            <div
              v-for="msg in radioMessages"
              :key="msg.id"
              class="radio-msg"
              :class="[`dir-${msg.direction}`, { player: msg.is_player_sent }]"
            >
              <div class="msg-header">
                <span class="msg-driver">{{ msg.driver?.code }}</span>
                <span class="msg-lap">Круг {{ msg.lap }}</span>
              </div>
              <div class="msg-text">{{ msg.message }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Race controls -->
    <div v-if="raceStarted && raceStatus !== 'completed'" class="race-controls-bar">
      <div class="controls-left">
        <button class="btn btn-secondary" @click="simLap">
          ⏭ Следующий круг
        </button>
        <button class="btn btn-secondary" @click="sim5Laps" :disabled="loading">
          ⏭⏭ +5 кругов
        </button>
        <button class="btn btn-warning" @click="simToEnd" :disabled="loading">
          ⚡ До финиша
        </button>
      </div>
      <div class="controls-right">
        <span class="stint-info" v-if="currentLap > 0">
          Следующие пит-стопы: {{ nextPitWindow }}
        </span>
      </div>
    </div>

    <!-- Race finished -->
    <div v-if="raceStatus === 'completed'" class="race-finish card">
      <div class="finish-header">🏁 Гонка завершена!</div>
      <div class="podium">
        <div v-for="r in podium" :key="r.driver_id" class="podium-item" :class="`podium-${r.finish_position}`">
          <div class="podium-pos">P{{ r.finish_position }}</div>
          <div class="podium-driver">{{ r.driver?.name }}</div>
          <div class="podium-team" :style="{ color: r.driver?.team?.color }">{{ r.driver?.team?.short_name }}</div>
          <div class="podium-points">+{{ r.points_scored }} очков</div>
        </div>
      </div>
      <div class="player-finish-summary">
        <div v-for="r in playerFinishResults" :key="r.driver_id" class="player-finish-item">
          <span class="pf-name">{{ r.driver?.name }}</span>
          <span class="pf-pos" :class="posClass(r.finish_position)">P{{ r.finish_position }}</span>
          <span class="pf-points">{{ r.points_scored }} очков</span>
          <span v-if="r.fastest_lap" class="pf-fl">⚡ Быстрейший круг</span>
        </div>
      </div>
      <div class="finish-actions">
        <router-link :to="`/season/${seasonId}/standings`" class="btn btn-secondary">
          📊 Таблица очков
        </router-link>
        <button class="btn btn-primary" @click="nextRound">
          Следующая гонка →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useGameStore } from '../stores/gameStore';
import api from '../api';

const route = useRoute();
const router = useRouter();
const store = useGameStore();

const raceStarted = ref(false);
const loading = ref(false);
const loadingText = ref('');
const currentLap = ref(0);
const totalLaps = ref(57);
const weather = ref('sunny');
const weekendName = ref('');
const raceStatus = ref('race');
const seasonId = ref(null);

const results = ref([]);
const radioMessages = ref([]);
const startCompounds = ref({});
const radioFeed = ref(null);

const weatherLabels = { sunny: '☀️ Солнечно', cloudy: '☁️ Облачно', rain: '🌧️ Дождь', heavy_rain: '⛈️ Ливень' };
const compoundLabels = { soft: 'С Мягкие', medium: 'М Средние', hard: 'Т Твёрдые', intermediate: 'И Переходные', wet: 'Д Дождевые' };
const compoundIcons = { soft: '🔴', medium: '🟡', hard: '⚪', intermediate: '🟢', wet: '🔵' };
const radioCommands = [
  { type: 'push', label: '🔥 Атака!', color: 'red' },
  { type: 'save', label: '🌿 Экономия', color: 'green' },
  { type: 'defend', label: '🛡️ Защита', color: 'blue' },
  { type: 'attack', label: '⚔️ DRS атака', color: 'orange' },
  { type: 'report', label: '📊 Доклад', color: 'gray' },
];
const availableCompounds = ['soft', 'medium', 'hard', 'intermediate', 'wet'];

const playerDrivers = computed(() => store.season?.player_team?.drivers || []);
const playerDriverIds = computed(() => playerDrivers.value.map(d => d.id));

function isPlayerDriver(driverId) { return playerDriverIds.value.includes(driverId); }

const sortedResults = computed(() =>
  [...results.value].sort((a, b) => (a.gap_to_leader || 0) - (b.gap_to_leader || 0))
);

const podium = computed(() =>
  [...results.value].filter(r => r.finish_position <= 3).sort((a, b) => a.finish_position - b.finish_position)
);

const playerFinishResults = computed(() =>
  results.value.filter(r => isPlayerDriver(r.driver_id))
);

const nextPitWindow = computed(() => {
  return playerDrivers.value.map(d => {
    const r = getDriverResult(d.id);
    if (!r) return '';
    const lapsSince = currentLap.value - (r.last_pit_lap || 0);
    const windows = { soft: 18, medium: 28, hard: 38 };
    const window = windows[r.tire_compound] || 25;
    const lapsLeft = window - lapsSince;
    return `${d.code}: ${lapsLeft > 0 ? `через ${lapsLeft} кр.` : 'СЕЙЧАС'}`;
  }).join(' | ');
});

function getDriverResult(driverId) {
  return results.value.find(r => r.driver_id == driverId);
}

function getDriverPosition(driverId) {
  const sorted = sortedResults.value;
  const idx = sorted.findIndex(r => r.driver_id == driverId);
  return idx >= 0 ? idx + 1 : '?';
}

function posClass(pos) {
  if (pos === 1) return 'pos-1';
  if (pos <= 3) return 'pos-top3';
  if (pos <= 10) return 'pos-top10';
  return 'pos-other';
}

onMounted(async () => {
  await store.loadCurrentSeason();
  seasonId.value = store.season?.id;

  const setupRes = await api.get(`/weekends/${route.params.id}/setup`);
  weekendName.value = setupRes.data.weekend.race_name;
  weather.value = setupRes.data.weekend.weather;
  totalLaps.value = setupRes.data.weekend.circuit?.lap_count || 57;
  raceStatus.value = setupRes.data.weekend.status;

  // Default compounds
  playerDrivers.value.forEach(d => { startCompounds.value[d.id] = 'medium'; });

  // Load existing race state if any
  try {
    const stateRes = await api.get(`/weekends/${route.params.id}/race/state`);
    if (stateRes.data.results?.length) {
      results.value = stateRes.data.results;
      radioMessages.value = stateRes.data.radio || [];
      raceStarted.value = true;
      if (results.value[0]) currentLap.value = results.value[0].current_lap || 0;
    }
  } catch {}
});

async function initRace() {
  loading.value = true;
  loadingText.value = 'Пилоты занимают позиции...';
  try {
    const { data } = await api.post(`/weekends/${route.params.id}/race/init`);
    results.value = data.results;
    radioMessages.value = data.radio || [];
    raceStarted.value = true;
    currentLap.value = 0;
  } finally {
    loading.value = false;
  }
}

async function simLap() {
  if (currentLap.value >= totalLaps.value) return;
  loading.value = true;
  loadingText.value = `Симуляция круга ${currentLap.value + 1}...`;
  try {
    const lap = currentLap.value + 1;
    const { data } = await api.post(`/weekends/${route.params.id}/race/lap`, { lap });
    results.value = data.results;
    radioMessages.value = data.radio || [];
    currentLap.value = lap;
    raceStatus.value = data.weekend?.status || raceStatus.value;
    await nextTick();
    if (radioFeed.value) radioFeed.value.scrollTop = radioFeed.value.scrollHeight;
  } finally {
    loading.value = false;
  }
}

async function sim5Laps() {
  for (let i = 0; i < 5 && currentLap.value < totalLaps.value; i++) {
    await simLap();
  }
}

async function simToEnd() {
  loading.value = true;
  loadingText.value = 'Симуляция гонки...';
  while (currentLap.value < totalLaps.value) {
    const lap = currentLap.value + 1;
    const { data } = await api.post(`/weekends/${route.params.id}/race/lap`, { lap });
    results.value = data.results;
    radioMessages.value = data.radio || [];
    currentLap.value = lap;
    raceStatus.value = data.weekend?.status || raceStatus.value;
    if (raceStatus.value === 'completed') break;
  }
  loading.value = false;
}

async function sendRadio(driverId, type) {
  const { data } = await api.post(`/weekends/${route.params.id}/race/radio/${driverId}`, {
    type,
    lap: currentLap.value,
  });
  const stateRes = await api.get(`/weekends/${route.params.id}/race/state`);
  radioMessages.value = stateRes.data.radio || [];
  await nextTick();
  if (radioFeed.value) radioFeed.value.scrollTop = radioFeed.value.scrollHeight;
}

async function callPitStop(driverId, compound) {
  loading.value = true;
  loadingText.value = 'Пит-стоп...';
  try {
    const { data } = await api.post(`/weekends/${route.params.id}/race/pit/${driverId}`, {
      compound,
      lap: currentLap.value,
    });
    results.value = data.results;
    radioMessages.value = data.radio || [];
  } finally {
    loading.value = false;
  }
}

async function nextRound() {
  const { data } = await api.post(`/weekends/${route.params.id}/next-round`);
  if (data.season_completed) {
    router.push(`/season/${seasonId.value}/standings`);
  } else {
    await store.loadSeason(seasonId.value);
    router.push(`/season/${seasonId.value}`);
  }
}
</script>

<style scoped>
.race-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.page-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
h1 { font-size: 26px; font-weight: 700; }
.race-meta { text-align: right; }
.lap-counter { font-size: 32px; font-weight: 900; color: white; }
.lap-current { color: #e10600; }
.lap-sep, .lap-total, .lap-label { color: #888; }
.lap-label { font-size: 16px; }
.weather-badge { font-size: 14px; margin-top: 4px; }

.init-screen { text-align: center; padding: 48px; max-width: 600px; margin: 0 auto; }
.init-icon { font-size: 64px; margin-bottom: 16px; }
.init-screen h2 { font-size: 24px; margin-bottom: 8px; }
.init-screen p { color: #888; margin-bottom: 24px; }
.compound-select { margin-bottom: 24px; display: flex; flex-direction: column; gap: 12px; text-align: left; }
.compound-row { display: flex; align-items: center; gap: 16px; }
.driver-label { font-size: 14px; color: #ccc; min-width: 160px; }
.compound-btns { display: flex; gap: 8px; }
.compound-btn {
  padding: 6px 14px;
  border-radius: 6px;
  border: 1px solid #2a2a3e;
  background: #13131f;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  opacity: 0.5;
  transition: all 0.15s;
}
.compound-btn.active { opacity: 1; border-color: currentColor; }

.loading-overlay { text-align: center; padding: 60px; color: #888; font-size: 18px; }
.sim-spinner {
  width: 40px; height: 40px;
  border: 3px solid #2a2a3e; border-top-color: #e10600;
  border-radius: 50%; animation: spin 0.8s linear infinite;
  margin: 0 auto 16px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.race-layout { display: grid; grid-template-columns: 1fr 380px; gap: 16px; }

/* Timing */
.timing-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.lap-pill { background: #e10600; color: white; font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 10px; }

.timing-list { display: flex; flex-direction: column; gap: 2px; max-height: 70vh; overflow-y: auto; }
.timing-row {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 10px; border-radius: 6px;
  transition: background 0.1s;
}
.timing-row:hover { background: #1a1a2e; }
.timing-row.player { background: #1a0a0a; border-left: 3px solid #e10600; }
.timing-row.dnf { opacity: 0.35; }

.t-pos { font-size: 13px; font-weight: 700; width: 22px; text-align: center; }
.pos-1 { color: #fbbf24; }
.pos-top3 { color: #e8e8e8; }
.pos-top10 { color: #60a5fa; }
.pos-other { color: #666; }

.t-team-bar { width: 3px; height: 24px; border-radius: 2px; flex-shrink: 0; }
.t-code { font-size: 12px; font-weight: 700; color: #e8e8e8; min-width: 36px; }
.t-tire { font-size: 14px; min-width: 20px; }
.t-gap { flex: 1; font-size: 12px; }
.t-leader { color: #fbbf24; font-weight: 600; font-size: 10px; }
.t-gap-val { color: #888; font-family: monospace; }
.t-pits { font-size: 11px; color: #666; }
.t-dnf { font-size: 11px; color: #f43f5e; font-weight: 600; }

/* Controls */
.controls-panel { display: flex; flex-direction: column; gap: 12px; }

.driver-control { padding: 14px; }
.dc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.dc-driver-name { font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.dc-number { font-weight: 900; color: #e10600; }
.dc-pos { font-size: 20px; font-weight: 700; color: #fbbf24; }

.dc-status { display: flex; gap: 12px; margin-bottom: 10px; font-size: 12px; }
.dc-tire { font-weight: 600; }
.dc-pits { color: #888; }
.dc-gap { color: #888; }

.radio-label, .pit-label { font-size: 11px; color: #888; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
.radio-btns { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 10px; }
.radio-btn {
  padding: 5px 10px; font-size: 11px; font-weight: 600;
  border: 1px solid #2a2a3e; border-radius: 6px;
  background: #0d0d14; cursor: pointer; color: #ccc;
  transition: all 0.15s;
}
.radio-btn:hover { background: #1a1a2e; border-color: #444; }
.radio-btn.radio-red:hover { border-color: #e10600; color: #e10600; }
.radio-btn.radio-green:hover { border-color: #34d399; color: #34d399; }
.radio-btn.radio-blue:hover { border-color: #60a5fa; color: #60a5fa; }
.radio-btn.radio-orange:hover { border-color: #fbbf24; color: #fbbf24; }

.pit-btns { display: flex; flex-wrap: wrap; gap: 4px; }
.pit-btn {
  padding: 6px 12px; font-size: 12px; font-weight: 600;
  border: 1px solid #2a2a3e; border-radius: 6px;
  background: #0d0d14; cursor: pointer;
  transition: all 0.15s;
}
.pit-btn:hover { background: #1a1a2e; border-color: currentColor; }
.pit-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.radio-feed { flex: 1; }
.radio-messages {
  display: flex; flex-direction: column; gap: 6px;
  max-height: 300px; overflow-y: auto;
  padding-right: 4px;
}
.radio-msg { padding: 8px 10px; border-radius: 6px; }
.radio-msg.dir-driver_to_engineer { background: #1a1a0a; border-left: 3px solid #fbbf24; }
.radio-msg.dir-engineer_to_driver { background: #0a1a1a; border-left: 3px solid #60a5fa; }
.radio-msg.player { border-left-color: #e10600; background: #1a0a0a; }
.msg-header { display: flex; gap: 8px; margin-bottom: 3px; }
.msg-driver { font-size: 11px; font-weight: 700; color: #e8e8e8; }
.msg-lap { font-size: 11px; color: #666; }
.msg-text { font-size: 13px; color: #ccc; }

.race-controls-bar {
  position: fixed; bottom: 0; left: 0; right: 0;
  background: rgba(13,13,20,0.96);
  backdrop-filter: blur(10px);
  border-top: 1px solid #2a2a3e;
  padding: 12px 24px;
  display: flex; justify-content: space-between; align-items: center;
  z-index: 50;
}
.controls-left { display: flex; gap: 10px; }
.stint-info { font-size: 13px; color: #888; }

.race-finish { padding: 32px; text-align: center; margin-top: 20px; }
.finish-header { font-size: 28px; font-weight: 700; margin-bottom: 24px; }
.podium { display: flex; justify-content: center; gap: 16px; margin-bottom: 24px; }
.podium-item { padding: 20px 24px; border-radius: 10px; min-width: 120px; }
.podium-1 { background: linear-gradient(180deg, #3d3000, #1a1400); border: 1px solid #fbbf24; }
.podium-2 { background: #1a1a2e; border: 1px solid #888; }
.podium-3 { background: #1a1200; border: 1px solid #b45309; }
.podium-pos { font-size: 28px; font-weight: 900; margin-bottom: 4px; }
.podium-driver { font-size: 14px; font-weight: 600; }
.podium-team { font-size: 12px; margin-top: 2px; }
.podium-points { font-size: 13px; color: #fbbf24; margin-top: 6px; font-weight: 600; }

.player-finish-summary { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
.player-finish-item { display: flex; gap: 16px; align-items: center; justify-content: center; font-size: 15px; }
.pf-name { color: #ccc; }
.pf-pos { font-weight: 700; font-size: 18px; }
.pf-points { color: #fbbf24; font-weight: 600; }
.pf-fl { color: #a78bfa; font-size: 13px; }

.finish-actions { display: flex; gap: 12px; justify-content: center; }
.finish-actions a { text-decoration: none; }
</style>
