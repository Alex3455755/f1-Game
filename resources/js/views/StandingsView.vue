<template>
  <div class="standings-view">
    <div class="stands-header">
      <div class="page-label">Чемпионат мира</div>
      <h1>Таблица очков</h1>
    </div>

    <div v-if="loading" class="loading">Загрузка...</div>

    <div v-else class="stands-layout">
      <div class="stands-table card">
        <div class="section-title">Личный зачёт</div>
        <div class="stands-list">
          <div class="stands-header-row">
            <span class="sh-pos">Pos</span>
            <span class="sh-driver">Пилот</span>
            <span class="sh-team">Команда</span>
            <span class="sh-wins">Побед</span>
            <span class="sh-podiums">Подиумов</span>
            <span class="sh-fl">FL</span>
            <span class="sh-pts">Очки</span>
          </div>
          <div
            v-for="(entry, i) in standings"
            :key="i"
            class="stands-row"
            :class="{ player: isPlayerDriver(entry.driver.id) }"
          >
            <span class="s-pos" :class="posClass(i + 1)">{{ i + 1 }}</span>
            <div class="s-driver">
              <div class="s-team-dot" :style="{ background: entry.team?.color }"></div>
              <div>
                <div class="s-driver-name">{{ entry.driver.name }}</div>
                <div class="s-driver-code">{{ entry.driver.code }}</div>
              </div>
            </div>
            <span class="s-team" :style="{ color: entry.team?.color }">{{ entry.team?.short_name }}</span>
            <span class="s-wins">{{ entry.wins }}</span>
            <span class="s-podiums">{{ entry.podiums }}</span>
            <span class="s-fl">{{ entry.fastest_laps }}</span>
            <span class="s-pts">{{ entry.points }}</span>
          </div>
        </div>
      </div>

      <div class="points-chart card">
        <div class="section-title">Разрыв от лидера</div>
        <div v-if="standings.length" class="chart-list">
          <div v-for="(e, i) in standings.slice(0, 10)" :key="i" class="chart-row">
            <span class="chart-name">{{ e.driver.code }}</span>
            <div class="chart-bar-wrap">
              <div
                class="chart-bar"
                :style="{
                  width: (e.points / standings[0].points * 100) + '%',
                  background: e.team?.color || '#e10600'
                }"
              ></div>
            </div>
            <span class="chart-pts">{{ e.points }}</span>
          </div>
        </div>

        <div class="season-nav">
          <router-link :to="`/season/${route.params.id}`" class="btn btn-secondary">
            ← Назад к сезону
          </router-link>
        </div>
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
const standings = ref([]);
const loading = ref(true);

const playerDriverIds = computed(() => store.season?.player_team?.drivers?.map(d => d.id) || []);
function isPlayerDriver(id) { return playerDriverIds.value.includes(id); }

function posClass(pos) {
  if (pos === 1) return 'pos-1';
  if (pos <= 3) return 'pos-top3';
  if (pos <= 10) return 'pos-top10';
  return 'pos-other';
}

onMounted(async () => {
  try {
    const { data } = await api.get(`/seasons/${route.params.id}/standings`);
    standings.value = data;
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.stands-header { margin-bottom: 24px; }
.page-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
h1 { font-size: 28px; font-weight: 700; }
.loading { text-align: center; padding: 60px; color: #888; }

.stands-layout { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }

.stands-header-row {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 12px; font-size: 11px; color: #666;
  text-transform: uppercase; letter-spacing: 0.5px;
  border-bottom: 1px solid #2a2a3e; margin-bottom: 4px;
}
.sh-pos { width: 32px; }
.sh-driver { flex: 1; }
.sh-team { width: 50px; }
.sh-wins, .sh-podiums, .sh-fl { width: 60px; text-align: center; }
.sh-pts { width: 60px; text-align: right; }

.stands-row {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 12px; border-radius: 6px;
  transition: background 0.1s;
}
.stands-row:hover { background: #1a1a2e; }
.stands-row.player { background: #1a0a0a; }

.s-pos { font-size: 14px; font-weight: 700; width: 32px; text-align: center; }
.pos-1 { color: #fbbf24; font-size: 16px; }
.pos-top3 { color: #e8e8e8; }
.pos-top10 { color: #60a5fa; }
.pos-other { color: #666; }

.s-driver { display: flex; align-items: center; gap: 10px; flex: 1; }
.s-team-dot { width: 8px; height: 28px; border-radius: 2px; flex-shrink: 0; }
.s-driver-name { font-size: 14px; font-weight: 600; color: #e8e8e8; }
.s-driver-code { font-size: 11px; color: #666; }
.s-team { font-size: 12px; font-weight: 600; width: 50px; }
.s-wins, .s-podiums, .s-fl { font-size: 13px; color: #888; width: 60px; text-align: center; }
.s-pts { font-size: 16px; font-weight: 700; color: #fbbf24; width: 60px; text-align: right; }

.chart-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.chart-row { display: flex; align-items: center; gap: 10px; }
.chart-name { font-size: 12px; font-weight: 600; color: #ccc; width: 36px; }
.chart-bar-wrap { flex: 1; height: 8px; background: #2a2a3e; border-radius: 4px; overflow: hidden; }
.chart-bar { height: 100%; border-radius: 4px; transition: width 0.5s; }
.chart-pts { font-size: 12px; color: #888; width: 36px; text-align: right; }

.season-nav { margin-top: 20px; }
.season-nav a { text-decoration: none; }
</style>
