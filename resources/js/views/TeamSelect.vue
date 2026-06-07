<template>
  <div class="team-select">
    <div class="hero">
      <div class="hero-content">
        <div class="hero-badge">2024 SEASON</div>
        <h1>F1 Engineer Simulator</h1>
        <p>Выбери команду, настрой болид, веди пилотов к победе</p>
      </div>
    </div>

    <div v-if="store.season" class="continue-season card">
      <div class="continue-info">
        <div class="continue-label">Активный сезон</div>
        <div class="continue-team" :style="{ color: store.season.player_team?.color }">
          {{ store.season.player_team?.name }}
        </div>
        <div class="continue-round">Гонка {{ store.season.current_round }} / 23</div>
      </div>
      <router-link :to="`/season/${store.season.id}`" class="btn btn-primary">
        Продолжить →
      </router-link>
    </div>

    <div class="section-title">Выбор команды</div>

    <div v-if="store.loading" class="loading">Загрузка команд...</div>

    <div v-else class="teams-grid">
      <div
        v-for="team in store.teams"
        :key="team.id"
        class="team-card"
        :class="{ selected: selectedTeam?.id === team.id }"
        :style="{ '--team-color': team.color }"
        @click="selectedTeam = team"
      >
        <div class="team-color-bar" :style="{ background: team.color }"></div>
        <div class="team-header">
          <h3>{{ team.name }}</h3>
          <div class="team-country">{{ team.base_country }}</div>
        </div>

        <div class="team-ratings">
          <div class="rating-bar">
            <span class="rating-label">Общий</span>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: team.overall_rating + '%', background: team.color }"></div>
            </div>
            <span class="rating-val">{{ team.overall_rating }}</span>
          </div>
          <div class="rating-bar">
            <span class="rating-label">Мотор</span>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: team.engine_rating + '%', background: '#60a5fa' }"></div>
            </div>
            <span class="rating-val">{{ team.engine_rating }}</span>
          </div>
          <div class="rating-bar">
            <span class="rating-label">Аэро</span>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: team.aero_rating + '%', background: '#34d399' }"></div>
            </div>
            <span class="rating-val">{{ team.aero_rating }}</span>
          </div>
          <div class="rating-bar">
            <span class="rating-label">Надёжн</span>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: team.reliability_rating + '%', background: '#fbbf24' }"></div>
            </div>
            <span class="rating-val">{{ team.reliability_rating }}</span>
          </div>
        </div>

        <div class="drivers-list">
          <div v-for="driver in team.drivers" :key="driver.id" class="driver-chip">
            <span class="driver-number" :style="{ color: team.color }">{{ driver.number }}</span>
            {{ driver.name }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="selectedTeam" class="start-panel">
      <div class="selected-info">
        <span>Выбрано:</span>
        <strong :style="{ color: selectedTeam.color }">{{ selectedTeam.name }}</strong>
      </div>
      <button class="btn btn-primary btn-large" :disabled="store.loading" @click="startSeason">
        {{ store.loading ? 'Запуск...' : 'Начать сезон 2024' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useGameStore } from '../stores/gameStore';

const store = useGameStore();
const router = useRouter();
const selectedTeam = ref(null);

onMounted(async () => {
  await store.loadCurrentSeason();
  store.fetchTeams();
});

async function startSeason() {
  const season = await store.startSeason(selectedTeam.value.id);
  router.push(`/season/${season.id}`);
}
</script>

<style scoped>
.hero {
  background: linear-gradient(135deg, #0d0d14 0%, #1a0a0a 50%, #0d0d14 100%);
  border: 1px solid #2a2a3e;
  border-radius: 16px;
  padding: 60px 40px;
  text-align: center;
  margin-bottom: 32px;
  position: relative;
  overflow: hidden;
}
.hero::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, #e10600, transparent);
}
.hero-badge {
  display: inline-block;
  background: #e10600;
  color: white;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 2px;
  padding: 4px 12px;
  border-radius: 20px;
  margin-bottom: 16px;
}
.hero h1 { font-size: 48px; font-weight: 900; color: white; margin-bottom: 12px; }
.hero p { font-size: 18px; color: #888; }

.continue-season {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
  border-color: #e10600;
}
.continue-label { font-size: 11px; color: #888; letter-spacing: 1px; text-transform: uppercase; }
.continue-team { font-size: 20px; font-weight: 700; }
.continue-round { font-size: 14px; color: #888; margin-top: 4px; }

.loading { text-align: center; color: #888; padding: 40px; }

.teams-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 16px;
  margin-bottom: 100px;
}

.team-card {
  background: #13131f;
  border: 1px solid #2a2a3e;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}
.team-card:hover { border-color: #444; transform: translateY(-2px); }
.team-card.selected { border-color: var(--team-color); box-shadow: 0 0 0 1px var(--team-color); }

.team-color-bar { height: 4px; }
.team-header { padding: 16px 16px 0; }
.team-header h3 { font-size: 16px; font-weight: 700; color: white; }
.team-country { font-size: 12px; color: #888; margin-top: 2px; }

.team-ratings { padding: 12px 16px; display: flex; flex-direction: column; gap: 6px; }
.rating-bar { display: flex; align-items: center; gap: 8px; }
.rating-label { font-size: 11px; color: #888; width: 50px; flex-shrink: 0; }
.bar-track { flex: 1; height: 4px; background: #2a2a3e; border-radius: 2px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 2px; transition: width 0.5s; }
.rating-val { font-size: 12px; font-weight: 600; color: #ccc; width: 26px; text-align: right; }

.drivers-list {
  padding: 8px 16px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.driver-chip { font-size: 13px; color: #bbb; display: flex; align-items: center; gap: 6px; }
.driver-number { font-weight: 700; font-size: 14px; }

.start-panel {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  background: rgba(13,13,20,0.95);
  backdrop-filter: blur(10px);
  border-top: 1px solid #2a2a3e;
  padding: 16px 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  z-index: 50;
}
.selected-info { font-size: 16px; color: #ccc; display: flex; gap: 8px; align-items: center; }
.btn-large { padding: 14px 36px; font-size: 16px; }
</style>
