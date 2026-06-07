<template>
  <div class="track-map">
    <svg :viewBox="track.viewBox" class="track-svg" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <!-- Glow for player driver -->
        <filter id="t-glow" x="-60%" y="-60%" width="220%" height="220%">
          <feGaussianBlur stdDeviation="5" result="blur"/>
          <feMerge>
            <feMergeNode in="blur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
        <!-- Subtle glow for other drivers -->
        <filter id="t-soft" x="-40%" y="-40%" width="180%" height="180%">
          <feGaussianBlur stdDeviation="2" result="blur"/>
          <feMerge>
            <feMergeNode in="blur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
        <radialGradient id="bg-grad" cx="50%" cy="50%" r="70%">
          <stop offset="0%" stop-color="#0d0d1a"/>
          <stop offset="100%" stop-color="#070710"/>
        </radialGradient>
      </defs>

      <!-- Background -->
      <rect width="100%" height="100%" fill="url(#bg-grad)"/>

      <!-- Track: outer edge (kerbs red/white) -->
      <path :d="track.path" fill="none"
        stroke="#c0392b" stroke-width="30"
        stroke-dasharray="18,18" stroke-linecap="butt"
        opacity="0.35"/>
      <!-- Track: asphalt surface -->
      <path :d="track.path" fill="none"
        stroke="#1c1c2e" stroke-width="26"
        stroke-linecap="round" stroke-linejoin="round"/>
      <!-- Track: surface detail -->
      <path :d="track.path" fill="none"
        stroke="#242438" stroke-width="22"
        stroke-linecap="round" stroke-linejoin="round"/>
      <!-- Track: racing groove (slightly darker centre) -->
      <path :d="track.path" fill="none"
        stroke="#161625" stroke-width="13"
        stroke-linecap="round" stroke-linejoin="round"/>

      <!-- Hidden path used for getPointAtLength -->
      <path ref="pathRef" :d="track.path"
        fill="none" stroke="none" stroke-width="0"/>

      <!-- Start / finish line -->
      <line v-if="sfLine"
        :x1="sfLine.x1" :y1="sfLine.y1"
        :x2="sfLine.x2" :y2="sfLine.y2"
        stroke="white" stroke-width="3" opacity="0.6"
        stroke-dasharray="4,3"/>

      <!-- DRS zone suggestion lines -->
      <line v-if="drsLine"
        :x1="drsLine.x1" :y1="drsLine.y1"
        :x2="drsLine.x2" :y2="drsLine.y2"
        stroke="#34d399" stroke-width="3" opacity="0.4"
        stroke-dasharray="4,3"/>

      <!-- ── DNF markers (cross, don't move) ── -->
      <g v-for="d in dnfList" :key="`dnf-${d.id}`">
        <circle :cx="d.x" :cy="d.y" r="7" fill="#1c1c2e" opacity="0.8"/>
        <text :x="d.x" :y="d.y + 3.5"
          text-anchor="middle" font-size="8" font-weight="900"
          fill="#f43f5e" style="pointer-events:none">✕</text>
      </g>

      <!-- ── Active drivers: shadow layer ── -->
      <circle v-for="d in activeList" :key="`sh-${d.id}`"
        :cx="d.x + 2" :cy="d.y + 2"
        :r="d.isPlayer ? 11 : 8"
        fill="black" opacity="0.45"/>

      <!-- ── Active drivers: dot + label ── -->
      <g v-for="d in activeList" :key="`dot-${d.id}`">
        <!-- Halo ring for player -->
        <circle v-if="d.isPlayer"
          :cx="d.x" :cy="d.y" r="14"
          fill="none" :stroke="d.color"
          stroke-width="2" opacity="0.4"/>
        <!-- Main dot -->
        <circle
          :cx="d.x" :cy="d.y"
          :r="d.isPlayer ? 10 : 7.5"
          :fill="d.color"
          :filter="d.isPlayer ? 'url(#t-glow)' : 'url(#t-soft)'"
          :stroke="d.isPlayer ? '#ffffff' : 'rgba(255,255,255,0.25)'"
          :stroke-width="d.isPlayer ? 2 : 1"/>
        <!-- Position number inside dot -->
        <text
          :x="d.x" :y="d.y + 3.5"
          text-anchor="middle"
          :font-size="d.isPlayer ? '7.5' : '6'"
          font-weight="900"
          fill="white"
          style="pointer-events:none; user-select:none">{{ d.pos }}</text>
      </g>
    </svg>

    <!-- Info overlay (bottom left corner) -->
    <div class="tm-info">
      <span class="tm-circuit">{{ circuit?.country || 'Трасса' }}</span>
      <span class="tm-lap" v-if="mode === 'race'">
        Круг <strong>{{ currentLap }}</strong>/{{ totalLaps }}
      </span>
      <span class="tm-lap" v-else>
        <strong>Квалификация</strong>
      </span>
    </div>

    <!-- Live legend: top-3 positions -->
    <div class="tm-legend">
      <div v-for="d in topThree" :key="`leg-${d.id}`" class="tm-leg-item">
        <span class="tm-leg-pos">{{ d.pos }}</span>
        <span class="tm-leg-dot" :style="{ background: d.color }"></span>
        <span class="tm-leg-code">{{ d.code }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps({
  /** Array of race results OR qualifying results */
  results: { type: Array, default: () => [] },
  currentLap: { type: Number, default: 0 },
  totalLaps: { type: Number, default: 57 },
  playerDriverIds: { type: Array, default: () => [] },
  circuit: { type: Object, default: null },
  /** 'race' | 'qualifying' */
  mode: { type: String, default: 'race' },
});

// ─── Track shape library ─────────────────────────────────────────────────────
const TRACKS = {
  // Generic permanent circuit (Bahrain / Spa feel)
  permanent_medium: {
    viewBox: '0 0 700 440',
    path: `M 350,420 L 140,420
           Q 70,420 70,350
           Q 70,280 140,250
           L 200,230
           Q 240,215 240,170
           L 240,130
           Q 240,90 280,80
           L 400,70
           Q 460,65 490,90
           L 530,120
           Q 560,145 560,180
           L 580,230
           Q 590,260 570,280
           L 550,300
           Q 530,320 550,340
           L 570,360
           Q 590,380 570,400
           L 500,420 L 350,420 Z`,
    sf: 0.02,   // fraction along path where start/finish is
    drs: 0.05,
  },
  // High-speed permanent (Monza / Baku straights)
  permanent_fast: {
    viewBox: '0 0 700 440',
    path: `M 350,420 L 150,420
           Q 80,420 80,350
           L 80,280
           Q 80,240 105,215
           L 125,195
           Q 148,178 122,158
           L 96,140
           Q 76,126 80,96
           L 86,60
           Q 90,30 158,30
           L 510,30
           Q 572,30 602,82
           L 622,140
           Q 636,196 626,265
           L 620,365
           Q 616,418 556,420
           L 350,420 Z`,
    sf: 0.02,
    drs: 0.06,
  },
  // Very twisty (Hungary-like)
  permanent_technical: {
    viewBox: '0 0 700 440',
    path: `M 360,435 L 185,435
           Q 112,435 102,362
           L 96,310
           Q 94,268 138,248
           L 190,237
           Q 222,228 234,198
           L 244,158
           Q 252,128 230,108
           L 202,88
           Q 182,73 192,53
           L 222,40
           Q 250,30 290,36
           L 362,46
           Q 402,57 414,82
           L 422,112
           Q 430,138 408,156
           L 388,172
           Q 368,188 380,218
           L 400,248
           Q 422,270 462,275
           L 522,276
           Q 572,276 594,312
           L 604,352
           Q 612,390 582,416
           L 522,434 L 360,435 Z`,
    sf: 0.02,
    drs: 0.03,
  },
  // Tight street circuit (Monaco-like)
  street_tight: {
    viewBox: '0 0 700 440',
    path: `M 350,410 L 180,410
           Q 110,410 100,345
           L 92,270
           Q 88,220 128,196
           L 200,168
           Q 228,158 228,128
           L 228,90
           Q 228,62 258,52
           L 332,50
           Q 378,50 400,80
           L 422,112
           Q 440,140 468,150
           L 556,164
           Q 598,174 618,210
           L 630,250
           Q 640,288 622,318
           L 594,348
           Q 574,368 552,378
           L 492,393
           Q 452,403 432,410
           L 350,410 Z`,
    sf: 0.02,
    drs: 0.85,
  },
  // Medium street circuit (Singapore/Baku)
  street_medium: {
    viewBox: '0 0 700 440',
    path: `M 355,432 L 165,432
           Q 92,432 82,360
           L 78,242
           Q 76,170 128,140
           L 200,120
           Q 232,110 242,80
           L 252,50
           Q 260,30 290,30
           L 420,30
           Q 460,30 482,60
           L 508,110
           Q 528,145 558,156
           L 608,166
           Q 648,176 660,220
           L 660,300
           Q 660,362 620,392
           L 572,420 L 500,432 L 355,432 Z`,
    sf: 0.02,
    drs: 0.88,
  },
};

function selectTrackKey(circuit) {
  if (!circuit) return 'permanent_medium';
  const { circuit_type, downforce_requirement, overtaking_difficulty } = circuit;
  if (circuit_type === 'street') {
    return overtaking_difficulty >= 80 ? 'street_tight' : 'street_medium';
  }
  if (downforce_requirement <= 25) return 'permanent_fast';
  if (overtaking_difficulty >= 75) return 'permanent_technical';
  return 'permanent_medium';
}

const track = computed(() => TRACKS[selectTrackKey(props.circuit)]);

// ─── SVG path ref ─────────────────────────────────────────────────────────────
const pathRef = ref(null);

// ─── Animation state ──────────────────────────────────────────────────────────
const animProgress = ref(0);   // 0..1, fraction of lap the LEADER is at
const activeList   = ref([]);
const dnfList      = ref([]);

let animId  = null;
let lastTs  = null;
const CYCLE_MS = 7000; // one full "animation lap" = 7 seconds wall-time

function runFrame(ts) {
  if (!lastTs) lastTs = ts;
  const dt = ts - lastTs;
  lastTs = ts;
  animProgress.value = (animProgress.value + dt / CYCLE_MS) % 1.0;
  computePositions();
  animId = requestAnimationFrame(runFrame);
}

function computePositions() {
  const el = pathRef.value;
  if (!el || !el.getTotalLength) return;

  const totalLen = el.getTotalLength();
  const leaderFrac = animProgress.value;

  const active = [];
  const dnf    = [];

  const sorted = [...props.results].sort((a, b) =>
    (a.gap_to_leader ?? a.position ?? 999) - (b.gap_to_leader ?? b.position ?? 999)
  );

  sorted.forEach((r, idx) => {
    const isDnf = r.status === 'dnf';
    const color = r.driver?.team?.color || '#888888';
    const code  = r.driver?.code || '---';

    // ── Calculate fraction along path ──────────────────────────────────────
    let gapFrac;

    if (props.mode === 'qualifying') {
      // Use best lap time gap to pole
      const bestTime = r.q3_time || r.q2_time || r.q1_time || 90;
      const poleTime = sorted[0]
        ? (sorted[0].q3_time || sorted[0].q2_time || sorted[0].q1_time || 90)
        : 90;
      const timeDiff = bestTime - poleTime;
      // Spread drivers: 0 = on pole lap, 5s gap ≈ spread across 25% of track
      gapFrac = Math.min(timeDiff / 5, 0.8) * 0.35;
    } else {
      // Race: gap_to_leader in seconds, spread assuming ~90s lap time
      const gapSec = r.gap_to_leader || 0;
      gapFrac = Math.min(gapSec / 90, 0.85) * 0.7;
    }

    const rawFrac  = leaderFrac - gapFrac;
    const fraction = ((rawFrac % 1) + 1) % 1;

    const pt = el.getPointAtLength(fraction * totalLen);

    const obj = {
      id:       r.driver_id,
      x:        pt.x,
      y:        pt.y,
      pos:      idx + 1,
      code,
      color,
      isPlayer: props.playerDriverIds.includes(r.driver_id),
    };

    isDnf ? dnf.push(obj) : active.push(obj);
  });

  activeList.value = active;
  dnfList.value    = dnf;
}

// ─── Start/finish & DRS lines ────────────────────────────────────────────────
const sfLine  = ref(null);
const drsLine = ref(null);

function buildStaticMarkers() {
  const el = pathRef.value;
  if (!el || !el.getTotalLength) return;

  const len = el.getTotalLength();
  const cfg = track.value;

  const buildCrossLine = (frac, half = 12) => {
    const pt0 = el.getPointAtLength(Math.max(0, frac * len - 1));
    const pt1 = el.getPointAtLength(Math.min(len, frac * len + 1));
    const dx = pt1.x - pt0.x;
    const dy = pt1.y - pt0.y;
    const dist = Math.sqrt(dx * dx + dy * dy) || 1;
    const nx = -dy / dist;
    const ny =  dx / dist;
    const pm = el.getPointAtLength(frac * len);
    return {
      x1: pm.x - nx * half,
      y1: pm.y - ny * half,
      x2: pm.x + nx * half,
      y2: pm.y + ny * half,
    };
  };

  sfLine.value  = buildCrossLine(cfg.sf || 0.02);
  drsLine.value = buildCrossLine(cfg.drs || 0.06, 9);
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
  await nextTick();
  buildStaticMarkers();
  animId = requestAnimationFrame(runFrame);
});

onUnmounted(() => {
  if (animId) cancelAnimationFrame(animId);
});

watch(() => props.circuit, async () => {
  await nextTick();
  buildStaticMarkers();
}, { deep: true });

watch(() => props.results, () => computePositions(), { deep: true });

// ─── Derived: top-3 for legend ────────────────────────────────────────────────
const topThree = computed(() => activeList.value.slice(0, 3));
</script>

<style scoped>
.track-map {
  position: relative;
  width: 100%;
  background: #07070f;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #2a2a3e;
}

.track-svg {
  width: 100%;
  height: 100%;
  display: block;
}

/* Info bar – bottom-left */
.tm-info {
  position: absolute;
  bottom: 10px;
  left: 14px;
  display: flex;
  flex-direction: column;
  gap: 1px;
  pointer-events: none;
}
.tm-circuit {
  font-size: 11px;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.tm-lap {
  font-size: 13px;
  color: #aaa;
}
.tm-lap strong {
  color: #e10600;
}

/* Legend – bottom-right */
.tm-legend {
  position: absolute;
  bottom: 10px;
  right: 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  pointer-events: none;
}
.tm-leg-item {
  display: flex;
  align-items: center;
  gap: 5px;
}
.tm-leg-pos {
  font-size: 10px;
  color: #666;
  width: 12px;
  text-align: right;
}
.tm-leg-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.tm-leg-code {
  font-size: 11px;
  font-weight: 700;
  color: #ccc;
}
</style>
