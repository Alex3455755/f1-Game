<template>
  <div v-if="data">
    <div class="setup-header">
      <div>
        <div class="page-label">Настройка болида</div>
        <h1>{{ data.weekend.race_name }}</h1>
        <div class="circuit-meta">
          {{ data.weekend.circuit?.country }} · {{ data.weekend.circuit?.lap_count }} кругов ·
          <span :class="`weather-${data.weekend.weather}`">{{ weatherLabels[data.weekend.weather] }}</span>
        </div>
      </div>
      <div class="circuit-hints-toggle">
        <button class="btn btn-secondary" @click="loadHints(activeDriverId)">💡 Подсказки инженера</button>
      </div>
    </div>

    <div class="hints-panel" v-if="hints.length">
      <div v-for="hint in hints" :key="hint.type" class="hint-item">
        <span class="hint-icon">{{ hintIcons[hint.type] || '💡' }}</span>
        <span>{{ hint.text }}</span>
      </div>
    </div>

    <div class="setup-layout">
      <!-- Driver tabs -->
      <div class="driver-tabs">
        <button
          v-for="setup in data.setups"
          :key="setup.driver_id"
          class="driver-tab"
          :class="{ active: activeDriverId == setup.driver_id }"
          @click="activeDriverId = setup.driver_id"
        >
          <span class="tab-number">{{ setup.driver?.number }}</span>
          {{ setup.driver?.name }}
        </button>
      </div>

      <!-- Setup form -->
      <div v-if="activeSetup" class="setup-form card">
        <div class="setup-sections">
          <!-- Aerodynamics -->
          <div class="setup-section">
            <h3>🌊 Аэродинамика</h3>
            <div class="setup-group">
              <label>Переднее антикрыло</label>
              <div class="slider-row">
                <span class="slider-min">1</span>
                <input type="range" min="1" max="11" v-model.number="activeSetup.front_wing"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">11</span>
                <span class="slider-val">{{ activeSetup.front_wing }}</span>
              </div>
              <div class="slider-hint">
                <span v-if="activeSetup.front_wing <= 3" class="hint-low">Минимальная прижимная сила — максимальная скорость</span>
                <span v-else-if="activeSetup.front_wing >= 9" class="hint-high">Высокая прижимная сила — лучший поворотный баланс</span>
                <span v-else class="hint-mid">Сбалансированная настройка</span>
              </div>
            </div>
            <div class="setup-group">
              <label>Заднее антикрыло</label>
              <div class="slider-row">
                <span class="slider-min">1</span>
                <input type="range" min="1" max="11" v-model.number="activeSetup.rear_wing"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">11</span>
                <span class="slider-val">{{ activeSetup.rear_wing }}</span>
              </div>
            </div>
          </div>

          <!-- Suspension -->
          <div class="setup-section">
            <h3>🔧 Подвеска</h3>
            <div class="setup-group">
              <label>Жёсткость подвески</label>
              <div class="slider-row">
                <span class="slider-min">1</span>
                <input type="range" min="1" max="10" v-model.number="activeSetup.suspension_stiffness"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">10</span>
                <span class="slider-val">{{ activeSetup.suspension_stiffness }}</span>
              </div>
              <div class="slider-hint">
                <span v-if="activeSetup.suspension_stiffness <= 3" class="hint-low">Мягкая — лучше на кочках, хуже в поворотах</span>
                <span v-else-if="activeSetup.suspension_stiffness >= 8" class="hint-high">Жёсткая — быстрее в поворотах, хуже по балансу</span>
                <span v-else class="hint-mid">Компромисс</span>
              </div>
            </div>
            <div class="setup-group">
              <label>Дорожный просвет</label>
              <div class="slider-row">
                <span class="slider-min">1</span>
                <input type="range" min="1" max="10" v-model.number="activeSetup.ride_height"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">10</span>
                <span class="slider-val">{{ activeSetup.ride_height }}</span>
              </div>
            </div>
          </div>

          <!-- Brakes & Differential -->
          <div class="setup-section">
            <h3>🛞 Тормоза и дифференциал</h3>
            <div class="setup-group">
              <label>Тормозной баланс (%)</label>
              <div class="slider-row">
                <span class="slider-min">45</span>
                <input type="range" min="45" max="65" v-model.number="activeSetup.brake_bias"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">65</span>
                <span class="slider-val">{{ activeSetup.brake_bias }}%</span>
              </div>
              <div class="slider-hint">
                <span v-if="activeSetup.brake_bias < 52" class="hint-low">Перевес назад — риск вращения</span>
                <span v-else-if="activeSetup.brake_bias > 58" class="hint-high">Перевес вперёд — блокировка передних</span>
                <span v-else class="hint-mid">Оптимальный баланс</span>
              </div>
            </div>
            <div class="setup-group">
              <label>Дифференциал (%)</label>
              <div class="slider-row">
                <span class="slider-min">30</span>
                <input type="range" min="30" max="70" v-model.number="activeSetup.differential"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">70</span>
                <span class="slider-val">{{ activeSetup.differential }}%</span>
              </div>
            </div>
          </div>

          <!-- Tire pressure -->
          <div class="setup-section">
            <h3>🔵 Давление в шинах</h3>
            <div class="setup-group">
              <label>Передние (psi)</label>
              <div class="slider-row">
                <span class="slider-min">20</span>
                <input type="range" min="20" max="26" step="0.5" v-model.number="activeSetup.tire_pressure_front"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">26</span>
                <span class="slider-val">{{ activeSetup.tire_pressure_front }}</span>
              </div>
            </div>
            <div class="setup-group">
              <label>Задние (psi)</label>
              <div class="slider-row">
                <span class="slider-min">19</span>
                <input type="range" min="19" max="25" step="0.5" v-model.number="activeSetup.tire_pressure_rear"
                  @change="saveSetup" class="slider" />
                <span class="slider-max">25</span>
                <span class="slider-val">{{ activeSetup.tire_pressure_rear }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="setup-summary">
          <div class="summary-item">
            <span>⬇️ Прижимная сила</span>
            <strong>{{ Math.round((activeSetup.front_wing + activeSetup.rear_wing) / 22 * 100) }}%</strong>
          </div>
          <div class="summary-item">
            <span>⚡ Скорость на прямых</span>
            <strong>{{ Math.round(100 - (activeSetup.front_wing + activeSetup.rear_wing) / 22 * 30) }}%</strong>
          </div>
        </div>
      </div>
    </div>

    <div class="proceed-panel">
      <div class="saved-indicator" :class="{ visible: saved }">✓ Настройки сохранены</div>
      <router-link :to="`/weekend/${route.params.id}/qualifying`" class="btn btn-primary btn-large">
        Перейти к квалификации →
      </router-link>
    </div>
  </div>
  <div v-else class="loading">Загрузка настроек...</div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api';

const route = useRoute();
const data = ref(null);
const hints = ref([]);
const saved = ref(false);
const activeDriverId = ref(null);

const weatherLabels = {
  sunny: '☀️ Солнечно', cloudy: '☁️ Облачно',
  rain: '🌧️ Дождь', heavy_rain: '⛈️ Ливень',
};
const hintIcons = { wings: '🌊', tires: '🔵', weather: '🌧️' };

const activeSetup = computed(() => {
  if (!data.value) return null;
  return data.value.setups.find(s => s.driver_id == activeDriverId.value);
});

onMounted(async () => {
  const res = await api.get(`/weekends/${route.params.id}/setup`);
  data.value = res.data;
  activeDriverId.value = res.data.setups[0]?.driver_id;
});

async function saveSetup() {
  if (!activeSetup.value) return;
  await api.put(
    `/weekends/${route.params.id}/setup/${activeDriverId.value}`,
    activeSetup.value
  );
  saved.value = true;
  setTimeout(() => saved.value = false, 2000);
}

async function loadHints(driverId) {
  const res = await api.get(`/weekends/${route.params.id}/setup/${driverId}/hint`);
  hints.value = res.data;
}
</script>

<style scoped>
.setup-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}
.page-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
h1 { font-size: 28px; font-weight: 700; }
.circuit-meta { font-size: 14px; color: #888; margin-top: 4px; }

.hints-panel {
  background: #1a1a0a;
  border: 1px solid #3d3d00;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.hint-item { display: flex; gap: 10px; font-size: 14px; color: #fbbf24; }

.setup-layout { display: flex; flex-direction: column; gap: 16px; }

.driver-tabs { display: flex; gap: 8px; }
.driver-tab {
  padding: 10px 20px;
  background: #13131f;
  border: 1px solid #2a2a3e;
  border-radius: 8px;
  color: #888;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  gap: 8px;
}
.driver-tab.active { border-color: #e10600; color: white; }
.tab-number { font-weight: 700; color: #e10600; }

.setup-sections { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.setup-section h3 { font-size: 14px; font-weight: 600; color: #aaa; margin-bottom: 12px; }

.setup-group { margin-bottom: 14px; }
.setup-group label { display: block; font-size: 13px; color: #888; margin-bottom: 6px; }

.slider-row { display: flex; align-items: center; gap: 8px; }
.slider-min, .slider-max { font-size: 11px; color: #666; width: 20px; }
.slider { flex: 1; height: 4px; accent-color: #e10600; }
.slider-val {
  font-size: 14px;
  font-weight: 700;
  color: #e10600;
  width: 40px;
  text-align: right;
}

.slider-hint { margin-top: 4px; font-size: 11px; }
.hint-low { color: #60a5fa; }
.hint-high { color: #f43f5e; }
.hint-mid { color: #34d399; }

.setup-summary {
  display: flex;
  gap: 24px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #2a2a3e;
}
.summary-item { display: flex; flex-direction: column; gap: 2px; }
.summary-item span { font-size: 12px; color: #888; }
.summary-item strong { font-size: 20px; font-weight: 700; color: #fbbf24; }

.proceed-panel {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}
.saved-indicator {
  font-size: 14px;
  color: #34d399;
  opacity: 0;
  transition: opacity 0.3s;
}
.saved-indicator.visible { opacity: 1; }
.btn-large { padding: 14px 32px; font-size: 15px; text-decoration: none; }
.loading { text-align: center; padding: 60px; color: #888; }
</style>
