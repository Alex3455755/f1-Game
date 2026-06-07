import { defineStore } from 'pinia';
import api from '../api';

export const useGameStore = defineStore('game', {
    state: () => ({
        season: null,
        currentWeekend: null,
        teams: [],
        loading: false,
        error: null,
    }),

    actions: {
        async fetchTeams() {
            this.loading = true;
            try {
                const { data } = await api.get('/teams');
                this.teams = data;
            } finally {
                this.loading = false;
            }
        },

        async startSeason(teamId) {
            this.loading = true;
            try {
                const { data } = await api.post('/seasons', { team_id: teamId });
                this.season = data;
                return data;
            } finally {
                this.loading = false;
            }
        },

        async loadCurrentSeason() {
            try {
                const { data } = await api.get('/seasons/current');
                this.season = data;
                return data;
            } catch {
                this.season = null;
            }
        },

        async loadSeason(id) {
            const { data } = await api.get(`/seasons/${id}`);
            this.season = data;
            return data;
        },
    },
});
