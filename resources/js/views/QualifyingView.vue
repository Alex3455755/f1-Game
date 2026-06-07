<template>
  <div class="qualifying-view">
    <div class="q-header">
      <div>
        <div class="page-label">Квалификация</div>
        <h1>{{ weekendName }}</h1>
      </div>
      <div class="q-session-badges">
        <span class="session-badge" :class="{ done: results.length > 0 }">Q1</span>
        <span class="session-badge" :class="{ done: results.length > 0 }">Q2</span>
        <span class="session-badge" :class="{ done: results.length > 0 }">Q3</span>
      </div>
    </div>

    <!-- Pre-simulation -->
    <div v-if="!results.length && !loading" class="pre-qual card">
      <div class="pre-qual-info">
        <div class="pre-qual-icon">🏁</div>
        <div>
          <h2>Готовы к квалификации?</h2>
          <p>20 пилотов выйдут на трассу. Q1 (5 выбывают) → Q2 (5 выбывают) → Q3 (топ-10 за поул).</p>
          <p class="hint-text">Твои настройки болида влияют на время круга. Чем точнее настройка под трассу — тем лучше результат.</p>
        </div>
      </div>
      <button class="btn btn-primary btn-large" @click="simulate">
        🚦 Запустить квалификацию
      </button>
    </div>

    <div v-if="loading" class="simulating">
      <div class="sim-spinner"></div>
      <div>Пилоты на трассе...</div>
    </div>

    <!-- Results -->
    <div v-if="results.length" class="results-layout">
      <div class="results-table card">
        <div class="section-title">Стартовая решётка</div>
        <div class="results-list">
          <div
            v-for="(r, i) in results"
            :key="r.id"
            class="result-row"
            :class="{ player: isPlayerDriver(r.driver_id), eliminated: r.q1_eliminated || r.q2_eliminated }"
          >
            <div class="result-pos" :class="posClass(r.position)">{{ r.position }}</div>
            <div class="team-stripe" :style="{ background: r.driver?.team?.color }"></div>
            <div class="result-driver">
              <span class="driver-code-sm">{{ r.driver?.code }}</span>
              <span class="driver-name-sm">{{ r.driver?.name }}</span>
            </div>
            <div class="result-team">{{ r.driver?.team?.short_name }}</div>
            <div class="result-times">
              <span class="q-time" :class="{ best: !r.q1_eliminated }">
                Q1: {{ formatTime(r.q1_time) }}
              </span>
              <span v-if="r.q2_time" class="q-time" :class="{ best: !r.q2_eliminated }">
                Q2: {{ formatTime(r.q2_time) }}
              </span>
              <span v-if="r.q3_time" class="q-time best">
                Q3: {{ formatTime(r.q3_time) }}
              </span>
            </div>
            <div class="result-eliminated">
              <span v-if="r.q1_eliminated" class="elim-badge">OUT Q1</span>
              <span v-else-if="r.q2_eliminated" class="elim-badge">OUT Q2</span>
              <span v-else-if="r.position === 1" class="pole-badge">POLE</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Player drivers analysis -->
      <div class="player-analysis card">
        <div class="section-title">Анализ твоих пилотов</div>
        <div v-for="r in playerResults" :key="r.id" class="player-result">
          <div class="player-pos-circle" :class="posClass(r.position)">P{{ r.position }}</div>
          <div class="player-result-info">
            <div class="player-result-name">{{ r.driver?.name }}</div>
            <div class="player-result-detail">
              <span v-if="r.q3_time">Лучшее время Q3: {{ formatTime(r.q3_time) }}</span>
              <span v-else-if="r.q2_time">Выбыл в Q2, лучшее: {{ formatTime(r.q2_time) }}</span>
              <span v-else>Выбыл в Q1, лучшее: {{ formatTime(r.q1_time) }}</span>
            </div>
            <div class="player-result-comment">{{ getPositionComment(r.position) }}</div>
          </div>
        </div>

        <router-link :to="`/weekend/${route.params.id}/race`" class="btn btn-success btn-large proceed-btn">
          🏎️ Начать гонку →
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useGameStore } from '../stores/gameStore';
import api from '../api';

const route = useRoute();
const store = useGameStore();
const results = ref([]);
const loading = ref(false);
const weekendName = ref('');

onMounted(async () => {
  const { data } = await api.get(`/weekends/${route.params.id}/setup`);
  weekendName.value = data.weekend.race_name;

  // Load existing results if available
  try {
    const qRes = await api.get(`/weekends/${route.params.id}/qualifying/results`);
    if (qRes.data.length) results.value = qRes.data;
  } catch {}
});

async function simulate() {
  loading.value = true;
  try {
    const { data } = await api.post(`/weekends/${route.params.id}/qualifying/simulate`);
    results.value = data.results;
  } finally {
    loading.value = false;
  }
}

const playerDriverIds = computed(() => {
  return store.season?.player_team?.drivers?.map(d => d.id) || [];
});

function isPlayerDriver(driverId) {
  return playerDriverIds.value.includes(driverId);
}

const playerResults = computed(() => results.value.filter(r => isPlayerDriver(r.driver_id)));

function formatTime(secs) {
  if (!secs) return '—';
  const m = Math.floor(secs / 60);
  const s = (secs % 60).toFixed(3).padStart(6, '0');
  return `${m}:${s}`;
}

function posClass(pos) {
  if (pos === 1) return 'pos-1';
  if (pos <= 3) return 'pos-top3';
  if (pos <= 10) return 'pos-top10';
  return 'pos-other';
}

function getPositionComment(pos) {
  if (pos === 1) return '🏆 Поул-позиция! Блестящий результат!';
  if (pos <= 3) return '🔥 Отличная позиция на старте!';
  if (pos <= 5) return '👍 Хорошая позиция для борьбы.';
  if (pos <= 10) return '✅ В топ-10, шансы на очки есть.';
  if (pos <= 15) return '⚠️ Трудный старт, нужна стратегия.';
  return '❌ Тяжело, придётся обгонять весь пелотон.';
}
</script>

<style scoped>
.q-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
.page-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
h1 { font-size: 28px; font-weight: 700; }

.q-session-badges { display: flex; gap: 8px; margin-top: 8px; }
.session-badge {
  padding: 6px 16px;
  border: 1px solid #2a2a3e;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  color: #666;
}
.session-badge.done { border-color: #e10600; color: #e10600; }

.pre-qual {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  padding: 32px;
}
.pre-qual-info { display: flex; gap: 20px; align-items: flex-start; }
.pre-qual-icon { font-size: 48px; }
.pre-qual-info h2 { font-size: 20px; margin-bottom: 8px; }
.pre-qual-info p { color: #888; font-size: 14px; margin-bottom: 6px; }
.hint-text { color: #fbbf24 !important; }

.simulating { text-align: center; padding: 60px; color: #888; font-size: 18px; }
.sim-spinner {
  width: 40px; height: 40px;
  border: 3px solid #2a2a3e;
  border-top-color: #e10600;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 16px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.results-layout { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }

.results-list { display: flex; flex-direction: column; gap: 2px; }
.result-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 6px;
  transition: background 0.15s;
}
.result-row:hover { background: #1a1a2e; }
.result-row.player { background: #1a0a0a; border-left: 3px solid #e10600; }
.result-row.eliminated { opacity: 0.4; }

.result-pos { font-size: 14px; font-weight: 700; width: 28px; text-align: center; }
.pos-1 { color: #fbbf24; font-size: 16px; }
.pos-top3 { color: #e8e8e8; }
.pos-top10 { color: #60a5fa; }
.pos-other { color: #666; }

.team-stripe { width: 3px; height: 28px; border-radius: 2px; flex-shrink: 0; }

.result-driver { display: flex; align-items: baseline; gap: 6px; min-width: 160px; }
.driver-code-sm { font-size: 13px; font-weight: 700; color: #e8e8e8; }
.driver-name-sm { font-size: 12px; color: #888; }
.result-team { font-size: 11px; color: #666; min-width: 40px; }

.result-times { display: flex; gap: 10px; flex: 1; }
.q-time { font-size: 12px; color: #666; font-family: monospace; }
.q-time.best { color: #e8e8e8; }

.elim-badge { font-size: 10px; color: #f43f5e; font-weight: 600; letter-spacing: 0.5px; }
.pole-badge { font-size: 10px; color: #fbbf24; font-weight: 600; letter-spacing: 0.5px; }

.player-result { display: flex; gap: 16px; align-items: center; margin-bottom: 16px; padding: 16px; background: #0d0d14; border-radius: 8px; }
.player-pos-circle {
  font-size: 20px;
  font-weight: 900;
  width: 56px; height: 56px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: #2a2a3e;
  flex-shrink: 0;
}
.player-pos-circle.pos-1 { background: #3d3000; color: #fbbf24; }
.player-pos-circle.pos-top3 { background: #2a2a3e; }
.player-result-name { font-size: 16px; font-weight: 600; }
.player-result-detail { font-size: 13px; color: #888; margin: 4px 0; }
.player-result-comment { font-size: 14px; }

.proceed-btn { width: 100%; text-align: center; text-decoration: none; margin-top: 20px; display: block; padding: 14px; }
.btn-large { padding: 14px 32px; font-size: 15px; }
</style>
