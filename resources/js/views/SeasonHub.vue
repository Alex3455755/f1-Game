<template>
  <div v-if="season">
    <div class="season-header">
      <div class="season-info">
        <div class="season-year">Сезон {{ season.year }}</div>
        <div class="team-name" :style="{ color: season.player_team?.color }">
          {{ season.player_team?.name }}
        </div>
      </div>
      <div class="round-progress">
        <span>Гонка {{ season.current_round }} из {{ season.race_weekends?.length || 23 }}</span>
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progressPct + '%' }"></div>
        </div>
      </div>
    </div>

    <div class="hub-grid">
      <!-- Calendar -->
      <div class="calendar-panel card">
        <div class="section-title">Календарь сезона</div>
        <div class="race-list">
          <div
            v-for="weekend in season.race_weekends"
            :key="weekend.id"
            class="race-item"
            :class="[`status-${weekend.status}`, { current: weekend.round_number === season.current_round }]"
          >
            <div class="race-round">{{ weekend.round_number }}</div>
            <div class="race-info">
              <div class="race-name">{{ weekend.race_name }}</div>
              <div class="race-circuit">{{ weekend.circuit?.country }} — {{ weekend.circuit?.name }}</div>
            </div>
            <div class="race-status-badge">
              <span v-if="weekend.status === 'completed'" class="badge-done">✓</span>
              <span v-else-if="weekend.status === 'upcoming'" class="badge-upcoming">—</span>
              <template v-else>
                <router-link
                  v-if="weekend.status === 'setup'"
                  :to="`/weekend/${weekend.id}/setup`"
                  class="btn btn-primary btn-sm"
                >Настройка</router-link>
                <router-link
                  v-else-if="weekend.status === 'qualifying'"
                  :to="`/weekend/${weekend.id}/qualifying`"
                  class="btn btn-warning btn-sm"
                >Квали</router-link>
                <router-link
                  v-else-if="weekend.status === 'race'"
                  :to="`/weekend/${weekend.id}/race`"
                  class="btn btn-success btn-sm"
                >Гонка</router-link>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Current weekend details -->
      <div v-if="currentWeekend" class="weekend-panel">
        <div class="card weekend-card">
          <div class="section-title">Текущий этап</div>
          <div class="circuit-info">
            <div class="circuit-flag">🏁</div>
            <div>
              <div class="circuit-name">{{ currentWeekend.race_name }}</div>
              <div class="circuit-details">{{ currentWeekend.circuit?.name }}</div>
              <div class="circuit-meta">
                <span>{{ currentWeekend.circuit?.lap_count }} кругов</span>
                <span>{{ currentWeekend.circuit?.lap_length }} км/круг</span>
                <span :class="`weather-${currentWeekend.weather}`">{{ weatherLabels[currentWeekend.weather] }}</span>
              </div>
            </div>
          </div>

          <div class="circuit-stats">
            <div class="stat">
              <div class="stat-label">Прижимная сила</div>
              <div class="stat-bar">
                <div class="stat-fill" :style="{ width: currentWeekend.circuit?.downforce_requirement + '%' }"></div>
              </div>
            </div>
            <div class="stat">
              <div class="stat-label">Износ резины</div>
              <div class="stat-bar">
                <div class="stat-fill warn" :style="{ width: currentWeekend.circuit?.tire_wear_rate + '%' }"></div>
              </div>
            </div>
            <div class="stat">
              <div class="stat-label">Сложность обгона</div>
              <div class="stat-bar">
                <div class="stat-fill danger" :style="{ width: currentWeekend.circuit?.overtaking_difficulty + '%' }"></div>
              </div>
            </div>
          </div>

          <div class="weekend-actions">
            <router-link
              v-if="currentWeekend.status === 'setup'"
              :to="`/weekend/${currentWeekend.id}/setup`"
              class="btn btn-primary"
            >⚙️ Настройка болида</router-link>
            <router-link
              v-else-if="currentWeekend.status === 'qualifying'"
              :to="`/weekend/${currentWeekend.id}/qualifying`"
              class="btn btn-warning"
            >🔥 Квалификация Q1-Q3</router-link>
            <router-link
              v-else-if="currentWeekend.status === 'race'"
              :to="`/weekend/${currentWeekend.id}/race`"
              class="btn btn-success"
            >🏎️ Гонка</router-link>
          </div>
        </div>

        <!-- Drivers -->
        <div class="card drivers-card">
          <div class="section-title">Твои пилоты</div>
          <div class="drivers-grid">
            <div v-for="driver in season.player_team?.drivers" :key="driver.id" class="driver-card">
              <div class="driver-number-big" :style="{ color: season.player_team?.color }">
                {{ driver.number }}
              </div>
              <div class="driver-details">
                <div class="driver-name-full">{{ driver.name }}</div>
                <div class="driver-code">{{ driver.code }} · {{ driver.nationality }}</div>
                <div class="driver-skills">
                  <div class="skill"><span>Скорость</span><strong>{{ driver.pace }}</strong></div>
                  <div class="skill"><span>Стабильность</span><strong>{{ driver.consistency }}</strong></div>
                  <div class="skill"><span>Дождь</span><strong>{{ driver.wet_skill }}</strong></div>
                  <div class="skill"><span>Обгон</span><strong>{{ driver.overtaking }}</strong></div>
                  <div class="skill"><span>Резина</span><strong>{{ driver.tire_management }}</strong></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else class="loading">Загрузка сезона...</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useGameStore } from '../stores/gameStore';

const store = useGameStore();
const route = useRoute();
const season = ref(null);

const weatherLabels = {
  sunny: '☀️ Солнечно', cloudy: '☁️ Облачно',
  rain: '🌧️ Дождь', heavy_rain: '⛈️ Ливень',
};

const currentWeekend = computed(() =>
  season.value?.race_weekends?.find(w => w.round_number === season.value.current_round)
);

const progressPct = computed(() => {
  if (!season.value) return 0;
  const total = season.value.race_weekends?.length || 23;
  return Math.round((season.value.current_round - 1) / total * 100);
});

onMounted(async () => {
  season.value = await store.loadSeason(route.params.id);
});
</script>

<style scoped>
.season-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding: 16px 20px;
  background: #13131f;
  border: 1px solid #2a2a3e;
  border-radius: 12px;
}
.season-year { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
.team-name { font-size: 24px; font-weight: 700; }
.round-progress { text-align: right; }
.round-progress span { font-size: 13px; color: #888; display: block; margin-bottom: 6px; }
.progress-bar { width: 200px; height: 4px; background: #2a2a3e; border-radius: 2px; }
.progress-fill { height: 100%; background: #e10600; border-radius: 2px; transition: width 0.5s; }

.hub-grid { display: grid; grid-template-columns: 1fr 400px; gap: 20px; }

.race-list { display: flex; flex-direction: column; gap: 4px; max-height: 600px; overflow-y: auto; }
.race-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid transparent;
  transition: all 0.15s;
}
.race-item:hover { background: #1a1a2e; }
.race-item.current { border-color: #e10600; background: #1a0a0a; }
.race-item.status-completed { opacity: 0.5; }

.race-round { font-size: 12px; color: #888; width: 24px; text-align: center; }
.race-info { flex: 1; }
.race-name { font-size: 14px; font-weight: 600; color: #e8e8e8; }
.race-circuit { font-size: 11px; color: #888; margin-top: 1px; }

.badge-done { color: #34d399; font-size: 16px; }
.badge-upcoming { color: #444; }
.btn-sm { padding: 4px 12px; font-size: 12px; }

.weekend-panel { display: flex; flex-direction: column; gap: 16px; }

.circuit-info { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px; }
.circuit-flag { font-size: 32px; }
.circuit-name { font-size: 18px; font-weight: 700; color: white; }
.circuit-details { font-size: 13px; color: #888; margin-top: 2px; }
.circuit-meta { display: flex; gap: 12px; margin-top: 6px; }
.circuit-meta span { font-size: 12px; color: #aaa; }

.circuit-stats { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
.stat { display: flex; align-items: center; gap: 10px; }
.stat-label { font-size: 12px; color: #888; width: 120px; }
.stat-bar { flex: 1; height: 4px; background: #2a2a3e; border-radius: 2px; overflow: hidden; }
.stat-fill { height: 100%; background: #60a5fa; border-radius: 2px; }
.stat-fill.warn { background: #fbbf24; }
.stat-fill.danger { background: #f43f5e; }

.weekend-actions { display: flex; gap: 10px; }
.weekend-actions .btn { flex: 1; text-align: center; text-decoration: none; padding: 12px; }

.drivers-grid { display: flex; flex-direction: column; gap: 12px; }
.driver-card { display: flex; gap: 16px; padding: 12px; background: #0d0d14; border-radius: 8px; }
.driver-number-big { font-size: 36px; font-weight: 900; line-height: 1; }
.driver-name-full { font-size: 16px; font-weight: 600; color: white; }
.driver-code { font-size: 12px; color: #888; margin: 2px 0 8px; }
.driver-skills { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; }
.skill { display: flex; justify-content: space-between; font-size: 12px; }
.skill span { color: #888; }
.skill strong { color: #e8e8e8; }

.loading { text-align: center; padding: 60px; color: #888; }
</style>
