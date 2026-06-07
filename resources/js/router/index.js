import { createRouter, createWebHashHistory } from 'vue-router';
import TeamSelect from '../views/TeamSelect.vue';
import SeasonHub from '../views/SeasonHub.vue';
import SetupView from '../views/SetupView.vue';
import QualifyingView from '../views/QualifyingView.vue';
import RaceView from '../views/RaceView.vue';
import StandingsView from '../views/StandingsView.vue';

const routes = [
    { path: '/', name: 'home', component: TeamSelect },
    { path: '/season/:id', name: 'season', component: SeasonHub },
    { path: '/weekend/:id/setup', name: 'setup', component: SetupView },
    { path: '/weekend/:id/qualifying', name: 'qualifying', component: QualifyingView },
    { path: '/weekend/:id/race', name: 'race', component: RaceView },
    { path: '/season/:id/standings', name: 'standings', component: StandingsView },
];

export default createRouter({
    history: createWebHashHistory(),
    routes,
});
