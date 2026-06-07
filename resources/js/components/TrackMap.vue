<template>
  <div class="track-map">
    <svg :viewBox="track.viewBox" class="track-svg" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <filter id="t-glow" x="-60%" y="-60%" width="220%" height="220%">
          <feGaussianBlur stdDeviation="5" result="blur"/>
          <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
        <filter id="t-soft" x="-40%" y="-40%" width="180%" height="180%">
          <feGaussianBlur stdDeviation="2" result="blur"/>
          <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
        </filter>
        <radialGradient id="bg-grad" cx="50%" cy="50%" r="70%">
          <stop offset="0%" stop-color="#0d0d1a"/>
          <stop offset="100%" stop-color="#070710"/>
        </radialGradient>
      </defs>

      <rect width="100%" height="100%" fill="url(#bg-grad)"/>

      <!-- Kerb dashes -->
      <path :d="track.path" fill="none" stroke="#c0392b" stroke-width="30"
        stroke-dasharray="18,18" stroke-linecap="butt" opacity="0.35"/>
      <!-- Asphalt -->
      <path :d="track.path" fill="none" stroke="#1c1c2e" stroke-width="26"
        stroke-linecap="round" stroke-linejoin="round"/>
      <!-- Surface -->
      <path :d="track.path" fill="none" stroke="#242438" stroke-width="22"
        stroke-linecap="round" stroke-linejoin="round"/>
      <!-- Racing groove -->
      <path :d="track.path" fill="none" stroke="#161625" stroke-width="13"
        stroke-linecap="round" stroke-linejoin="round"/>

      <!-- Hidden path for geometry calculations -->
      <path ref="pathRef" :d="track.path" fill="none" stroke="none" stroke-width="0"/>

      <!-- Start/finish line -->
      <line v-if="sfLine" :x1="sfLine.x1" :y1="sfLine.y1" :x2="sfLine.x2" :y2="sfLine.y2"
        stroke="white" stroke-width="3" opacity="0.6" stroke-dasharray="4,3"/>
      <!-- DRS zone -->
      <line v-if="drsLine" :x1="drsLine.x1" :y1="drsLine.y1" :x2="drsLine.x2" :y2="drsLine.y2"
        stroke="#34d399" stroke-width="3" opacity="0.4" stroke-dasharray="4,3"/>

      <!-- DNF markers -->
      <g v-for="d in dnfList" :key="`dnf-${d.id}`">
        <circle :cx="d.x" :cy="d.y" r="7" fill="#1c1c2e" opacity="0.8"/>
        <text :x="d.x" :y="d.y + 3.5" text-anchor="middle" font-size="8"
          font-weight="900" fill="#f43f5e" style="pointer-events:none">✕</text>
      </g>

      <!-- Driver shadows -->
      <circle v-for="d in activeList" :key="`sh-${d.id}`"
        :cx="d.x + 2" :cy="d.y + 2" :r="d.isPlayer ? 11 : 8"
        fill="black" opacity="0.45"/>

      <!-- Driver dots -->
      <g v-for="d in activeList" :key="`dot-${d.id}`">
        <circle v-if="d.isPlayer" :cx="d.x" :cy="d.y" r="14"
          fill="none" :stroke="d.color" stroke-width="2" opacity="0.4"/>
        <circle :cx="d.x" :cy="d.y" :r="d.isPlayer ? 10 : 7.5"
          :fill="d.color"
          :filter="d.isPlayer ? 'url(#t-glow)' : 'url(#t-soft)'"
          :stroke="d.isPlayer ? '#ffffff' : 'rgba(255,255,255,0.25)'"
          :stroke-width="d.isPlayer ? 2 : 1"/>
        <text :x="d.x" :y="d.y + 3.5" text-anchor="middle"
          :font-size="d.isPlayer ? '7.5' : '6'" font-weight="900"
          fill="white" style="pointer-events:none; user-select:none">{{ d.pos }}</text>
      </g>
    </svg>

    <div class="tm-info">
      <span class="tm-circuit">{{ circuit?.country || 'Трасса' }} · {{ circuit?.city || '' }}</span>
      <span class="tm-lap" v-if="mode === 'race'">
        Круг <strong>{{ currentLap }}</strong>/{{ totalLaps }}
      </span>
      <span class="tm-lap" v-else><strong>Квалификация</strong></span>
      <!-- Live lap timer (thousandths) -->
      <span class="tm-timer" v-if="mode === 'race' && currentLap > 0">{{ lapTimerStr }}</span>
    </div>

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

const props = defineProps({
  results:           { type: Array,  default: () => [] },
  currentLap:        { type: Number, default: 0 },
  totalLaps:         { type: Number, default: 57 },
  playerDriverIds:   { type: Array,  default: () => [] },
  circuit:           { type: Object, default: null },
  mode:              { type: String, default: 'race' },
  estimatedLapTime:  { type: Number, default: 90 },   // seconds per lap
});

const emit = defineEmits(['lap-complete']);

// ─── Real F1 circuit silhouettes (700×440 viewBox) ──────────────────────────
const TRACKS = {

  // Monaco – tight triangular street circuit
  monaco: {
    viewBox: '0 0 700 440',
    path: `M 100,390 L 380,390 Q 440,390 488,354 L 510,280
      Q 524,248 548,234 Q 574,220 578,192 Q 582,158 556,147
      Q 528,137 508,150 Q 486,164 476,188 Q 466,212 452,228
      Q 438,244 424,256 Q 408,268 394,254 Q 378,240 368,254
      Q 350,270 334,290 Q 318,312 305,336 Q 290,356 268,363
      Q 248,368 235,352 Q 224,336 228,313 Q 232,290 250,278
      Q 265,267 266,249 Q 267,230 250,217 Q 232,205 222,224
      Q 214,244 212,278 Q 210,314 200,348 Q 192,378 100,390 Z`,
    sf: 0.03, drs: 0.88,
  },

  // Silverstone – wide flowing hexagon
  silverstone: {
    viewBox: '0 0 700 440',
    path: `M 580,328 L 585,194 Q 590,160 562,136 Q 535,113 493,127
      Q 452,140 435,118 Q 410,95 368,95 L 211,95
      Q 153,95 126,135 Q 100,174 114,223 Q 128,272 165,294
      Q 203,316 248,328 Q 293,340 347,346 L 455,346
      Q 528,346 580,328 Z`,
    sf: 0.02, drs: 0.04,
  },

  // Monza – high-speed temple of speed oval
  monza: {
    viewBox: '0 0 700 440',
    path: `M 555,408 L 232,408 Q 146,408 118,342 L 110,228
      Q 106,175 80,152 Q 58,132 62,100 Q 66,68 106,58
      L 350,52 Q 468,52 535,60 Q 602,68 630,124
      Q 654,178 652,254 Q 648,330 616,372 Q 590,408 555,408 Z`,
    sf: 0.03, drs: 0.05,
  },

  // Spa-Francorchamps – long L-shaped circuit
  spa: {
    viewBox: '0 0 700 440',
    path: `M 598,118 L 600,242 Q 600,280 572,300 L 534,322
      Q 506,338 488,362 Q 470,386 448,397 Q 413,417 373,417
      L 213,417 Q 163,417 138,384 Q 115,352 123,310
      Q 131,270 107,248 Q 84,228 78,196 Q 72,157 98,133
      Q 124,110 172,110 L 352,110 Q 418,110 452,88
      Q 488,66 528,69 Q 574,73 598,118 Z`,
    sf: 0.02, drs: 0.05,
  },

  // Bahrain – boot shape with looping infield
  bahrain: {
    viewBox: '0 0 700 440',
    path: `M 562,394 L 378,394 Q 315,394 292,368 Q 268,342 282,302
      Q 294,265 267,249 Q 242,234 222,249 Q 202,264 203,294
      Q 204,324 186,340 Q 166,357 136,354 Q 100,350 84,316
      Q 68,280 78,242 Q 88,206 118,183 Q 152,158 175,122
      Q 193,95 216,72 Q 246,44 288,36 L 418,36
      Q 478,36 513,65 Q 546,95 556,140 Q 566,188 546,228
      Q 526,265 546,295 Q 565,322 592,328 Q 628,336 636,370
      Q 642,400 616,416 Q 595,426 562,394 Z`,
    sf: 0.02, drs: 0.05,
  },

  // Hungaroring – very twisty compact layout
  hungary: {
    viewBox: '0 0 700 440',
    path: `M 538,392 L 383,392 Q 337,392 315,370 Q 293,348 302,308
      Q 312,270 289,253 Q 266,237 244,253 Q 222,270 218,300
      Q 214,330 190,345 Q 163,360 133,346 Q 99,330 90,290
      Q 82,250 106,220 Q 130,190 131,158 Q 132,128 110,108
      Q 88,88 94,62 Q 100,38 140,32 L 307,32 Q 375,32 407,58
      Q 435,82 432,115 Q 429,148 406,165 Q 381,184 381,215
      Q 381,246 406,260 Q 432,274 460,268 Q 490,261 510,238
      Q 528,217 552,221 Q 577,227 589,254 Q 602,284 593,318
      Q 584,352 564,374 Q 550,390 538,392 Z`,
    sf: 0.02, drs: 0.04,
  },

  // Suzuka – figure-8 crossover
  suzuka: {
    viewBox: '0 0 700 440',
    path: `M 595,362 L 490,362 Q 450,362 433,336 Q 416,310 429,280
      Q 442,250 428,224 Q 414,198 387,192 Q 360,186 342,199
      Q 324,213 320,238 Q 316,264 332,282 Q 350,302 344,328
      Q 337,354 312,368 Q 284,381 247,381 L 143,381
      Q 93,381 78,341 Q 65,302 82,264 Q 99,227 133,212
      Q 168,197 191,166 Q 210,141 204,109 Q 198,77 220,54
      Q 244,30 291,28 Q 341,26 376,50 Q 408,72 411,108
      Q 414,142 434,162 Q 456,182 486,175 Q 518,168 541,145
      Q 564,122 595,120 Q 633,120 650,150 Q 663,178 655,212
      Q 645,248 617,268 Q 591,286 587,318 Q 583,348 595,362 Z`,
    sf: 0.03, drs: 0.06,
  },

  // Circuit of the Americas – large Turn 1 + complex
  cota: {
    viewBox: '0 0 700 440',
    path: `M 575,383 L 474,383 Q 434,383 416,353 L 394,297
      Q 382,267 358,252 Q 333,237 308,250 Q 284,263 271,292
      Q 258,322 232,340 Q 207,357 177,352 Q 144,346 127,317
      Q 112,290 120,257 Q 128,227 107,207 Q 88,190 74,160
      Q 61,130 78,100 Q 95,70 133,57 L 273,50 Q 338,48 381,67
      Q 421,85 433,120 Q 443,150 425,174 Q 408,197 411,227
      Q 415,260 441,274 Q 465,287 488,274 Q 513,260 521,230
      Q 529,200 551,187 Q 575,174 598,190 Q 621,207 628,240
      Q 635,274 628,314 Q 621,350 601,370 Q 588,384 575,383 Z`,
    sf: 0.03, drs: 0.05,
  },

  // Baku – ultra-long straight + tight old town
  baku: {
    viewBox: '0 0 700 440',
    path: `M 625,398 L 245,398 Q 196,398 175,372 Q 156,347 167,311
      Q 178,276 157,258 Q 136,240 112,251 Q 88,262 80,288
      Q 72,316 88,340 Q 102,362 98,385 Q 93,408 68,418
      Q 44,427 26,412 Q 12,398 18,373 L 18,115
      Q 18,78 48,58 Q 76,40 115,46 Q 152,53 161,86
      Q 168,112 143,134 Q 120,155 128,186 Q 136,216 164,226
      Q 192,236 216,222 Q 238,209 239,182 Q 240,155 261,138
      Q 282,122 308,120 Q 337,118 355,135 Q 371,152 368,178
      Q 364,204 385,220 Q 408,236 432,227 Q 456,219 463,195
      Q 470,170 492,157 Q 516,145 543,156 Q 569,168 577,196
      Q 584,224 573,252 Q 560,280 571,308 Q 582,336 610,345
      Q 640,355 650,382 Q 657,404 625,398 Z`,
    sf: 0.02, drs: 0.04,
  },

  // Abu Dhabi / Yas Marina – anticlockwise
  abudhabi: {
    viewBox: '0 0 700 440',
    path: `M 610,336 L 524,336 Q 489,336 474,311 Q 460,286 472,257
      Q 484,230 470,207 Q 456,185 428,183 Q 400,181 383,201
      Q 366,221 369,250 Q 372,278 352,294 Q 332,310 303,305
      Q 274,300 262,275 Q 250,250 264,224 Q 279,198 265,174
      Q 252,152 225,149 Q 194,146 177,167 Q 160,188 164,217
      Q 168,246 149,263 Q 129,280 102,276 Q 74,270 63,243
      Q 52,215 68,188 Q 84,162 110,150 Q 138,138 164,114
      Q 189,92 224,80 L 388,76 Q 452,76 487,106
      Q 516,134 515,172 Q 514,210 540,226 Q 568,243 597,232
      Q 628,220 638,192 Q 648,164 638,136 Q 628,109 640,85
      Q 653,60 678,64 Q 697,68 697,95 L 697,314
      Q 697,345 673,362 Q 648,378 622,372 Q 614,369 610,336 Z`,
    sf: 0.03, drs: 0.06,
  },

  // Interlagos / Brazil – anticlockwise teardrop
  interlagos: {
    viewBox: '0 0 700 440',
    path: `M 548,382 L 335,382 Q 280,382 254,354 Q 230,327 241,288
      Q 252,251 225,231 Q 199,212 171,222 Q 145,233 136,259
      Q 128,286 108,302 Q 86,318 59,307 Q 34,296 26,267
      Q 18,237 36,211 Q 54,185 67,152 Q 80,120 71,88
      Q 63,57 87,37 Q 111,17 155,15 L 348,15
      Q 428,15 468,48 Q 503,78 500,120 Q 497,160 522,181
      Q 548,202 577,194 Q 604,186 618,162 Q 631,137 624,107
      Q 617,77 640,58 Q 661,41 682,56 Q 698,70 695,100
      L 695,340 Q 695,378 666,396 Q 638,412 604,401
      Q 572,390 548,382 Z`,
    sf: 0.03, drs: 0.06,
  },

  // Singapore / Marina Bay – tight angular street
  singapore: {
    viewBox: '0 0 700 440',
    path: `M 90,395 L 545,395 Q 593,395 612,362 Q 628,330 614,297
      Q 600,265 572,253 Q 542,242 516,255 Q 491,268 475,296
      L 444,347 Q 425,372 390,378 L 165,378 Q 130,372 112,343
      Q 95,316 102,282 Q 110,249 134,233 Q 158,218 160,188
      Q 162,158 141,137 Q 121,117 124,87 Q 128,57 156,45
      Q 184,34 210,50 Q 232,64 232,92 Q 232,120 211,140
      Q 190,160 190,190 Q 190,220 212,240 Q 234,258 262,252
      Q 290,246 302,222 Q 313,198 300,170 Q 288,143 306,119
      Q 324,95 352,94 Q 382,93 400,115 Q 416,136 410,164
      Q 403,193 424,211 Q 447,228 472,221 Q 496,214 504,190
      Q 512,166 536,156 Q 561,147 582,162 Q 601,178 600,205
      Q 600,232 582,250 Q 563,268 566,296 Q 569,325 592,336
      Q 616,346 632,326 Q 645,308 640,282 L 640,90
      Q 640,58 610,45 Q 582,33 560,49 Q 540,63 540,92
      L 540,380 L 90,395 Z`,
    sf: 0.04, drs: 0.85,
  },

  // Jeddah – fast street circuit, long straight
  jeddah: {
    viewBox: '0 0 700 440',
    path: `M 90,418 L 90,78 Q 90,46 122,31 Q 154,17 187,30
      Q 216,43 222,74 Q 227,98 208,118 Q 189,138 192,167
      Q 196,196 220,207 Q 244,218 268,207 Q 291,197 297,172
      Q 303,148 325,136 Q 349,124 374,135 Q 398,146 402,172
      Q 406,198 388,218 Q 370,237 373,264 Q 376,292 400,303
      Q 424,313 448,302 Q 470,291 476,266 Q 482,242 505,231
      Q 529,221 550,234 Q 571,247 572,274 Q 573,302 552,318
      Q 531,335 536,362 Q 540,382 566,392 Q 591,402 610,387
      Q 628,372 628,348 L 628,78 Q 628,48 655,34 Q 680,22 697,38
      L 697,418 L 90,418 Z`,
    sf: 0.02, drs: 0.03,
  },

  // Canada / Montreal – island circuit
  canada: {
    viewBox: '0 0 700 440',
    path: `M 588,352 L 446,352 Q 410,352 394,324 L 378,282
      Q 368,254 342,242 Q 315,230 290,243 Q 266,256 258,283
      L 244,342 Q 237,370 210,384 Q 182,397 152,384
      Q 124,370 120,340 Q 118,314 140,300 Q 162,287 164,260
      Q 166,234 147,217 Q 129,202 112,211 Q 96,221 91,244
      Q 86,268 63,280 Q 40,292 21,274 Q 4,257 11,230
      L 11,142 Q 11,110 36,91 Q 60,73 94,80 Q 124,88 133,113
      Q 141,136 126,157 Q 111,178 118,204 Q 125,230 151,235
      Q 177,239 198,222 Q 218,206 220,180 Q 222,153 245,136
      Q 269,120 300,125 Q 330,131 343,156 Q 355,180 342,205
      Q 330,230 344,255 Q 358,278 388,278 Q 416,278 430,254
      Q 443,230 434,203 Q 424,176 444,157 Q 466,137 500,144
      Q 530,151 543,179 Q 554,206 543,237 Q 532,268 552,289
      Q 572,310 602,304 Q 630,297 641,270 Q 651,243 633,218
      Q 616,194 627,168 Q 638,142 665,136 Q 688,131 697,152
      L 697,334 Q 697,368 671,386 Q 645,402 618,391
      Q 598,380 588,352 Z`,
    sf: 0.03, drs: 0.06,
  },

  // Zandvoort – compact oval with banking
  zandvoort: {
    viewBox: '0 0 700 440',
    path: `M 562,368 L 246,368 Q 174,368 144,324 Q 116,282 128,233
      Q 140,187 113,163 Q 90,141 77,109 Q 65,79 87,53
      Q 108,28 152,25 Q 193,22 226,49 Q 256,74 255,110
      Q 254,144 277,161 Q 300,178 329,169 Q 358,160 371,134
      Q 382,110 408,96 Q 436,82 466,92 Q 495,103 506,131
      Q 516,157 502,182 Q 487,205 499,232 Q 512,260 540,265
      Q 568,270 584,248 Q 598,228 593,200 Q 587,172 606,154
      Q 625,136 650,145 Q 673,156 677,184 Q 681,214 664,238
      Q 648,262 651,292 Q 654,322 635,347 Q 617,371 592,375
      Q 577,378 562,368 Z`,
    sf: 0.02, drs: 0.05,
  },

  // Albert Park / Australia – park circuit
  australia: {
    viewBox: '0 0 700 440',
    path: `M 575,360 L 460,360 Q 428,360 412,334 Q 396,308 408,278
      Q 420,250 404,226 Q 388,203 360,200 Q 332,197 314,218
      Q 296,240 298,268 Q 300,296 280,314 Q 258,332 228,328
      Q 196,323 180,296 Q 165,270 174,240 Q 184,210 168,188
      Q 152,166 125,162 Q 98,158 82,180 Q 66,202 72,232
      Q 78,262 98,280 Q 118,298 118,328 Q 118,358 96,376
      Q 74,393 50,382 Q 28,370 25,344 L 25,140
      Q 25,108 52,88 Q 78,70 112,78 Q 143,86 152,112
      Q 160,136 143,157 Q 126,178 133,204 Q 140,230 167,238
      Q 194,246 215,228 Q 235,210 234,183 Q 232,156 252,138
      Q 272,120 303,120 L 450,120 Q 510,120 545,154
      Q 578,188 576,232 Q 574,270 598,288 Q 622,306 648,296
      Q 672,286 678,260 Q 684,234 666,214 Q 650,196 660,170
      Q 670,146 694,140 Q 698,138 698,165 L 698,342
      Q 698,375 672,392 Q 646,408 620,397 Q 598,388 575,360 Z`,
    sf: 0.03, drs: 0.05,
  },

  // Generic fallbacks
  permanent_medium: {
    viewBox: '0 0 700 440',
    path: `M 350,420 L 140,420 Q 70,420 70,350 Q 70,280 140,250 L 200,230
      Q 240,215 240,170 L 240,130 Q 240,90 280,80 L 400,70
      Q 460,65 490,90 L 530,120 Q 560,145 560,180 L 580,230
      Q 590,260 570,280 L 550,300 Q 530,320 550,340 L 570,360
      Q 590,380 570,400 L 500,420 L 350,420 Z`,
    sf: 0.02, drs: 0.05,
  },
  permanent_fast: {
    viewBox: '0 0 700 440',
    path: `M 350,420 L 150,420 Q 80,420 80,350 L 80,280 Q 80,240 105,215
      L 125,195 Q 148,178 122,158 L 96,140 Q 76,126 80,96 L 86,60
      Q 90,30 158,30 L 510,30 Q 572,30 602,82 L 622,140
      Q 636,196 626,265 L 620,365 Q 616,418 556,420 L 350,420 Z`,
    sf: 0.02, drs: 0.06,
  },
  permanent_technical: {
    viewBox: '0 0 700 440',
    path: `M 360,435 L 185,435 Q 112,435 102,362 L 96,310
      Q 94,268 138,248 L 190,237 Q 222,228 234,198 L 244,158
      Q 252,128 230,108 L 202,88 Q 182,73 192,53 L 222,40
      Q 250,30 290,36 L 362,46 Q 402,57 414,82 L 422,112
      Q 430,138 408,156 L 388,172 Q 368,188 380,218 L 400,248
      Q 422,270 462,275 L 522,276 Q 572,276 594,312 L 604,352
      Q 612,390 582,416 L 522,434 L 360,435 Z`,
    sf: 0.02, drs: 0.03,
  },
};

// ─── Circuit name → track key ────────────────────────────────────────────────
function selectTrackKey(circuit) {
  if (!circuit) return 'permanent_medium';
  const name = (circuit.name || '').toLowerCase();

  // Exact-name matches first (highest priority)
  if (name.includes('monaco') || name.includes('monte carlo'))         return 'monaco';
  if (name.includes('silverstone'))                                     return 'silverstone';
  if (name.includes('monza') || name.includes('nazionale monza'))      return 'monza';
  if (name.includes('spa') || name.includes('francorchamps'))          return 'spa';
  if (name.includes('suzuka'))                                          return 'suzuka';
  if (name.includes('bahrain'))                                         return 'bahrain';
  if (name.includes('hungaroring'))                                     return 'hungary';
  if (name.includes('americas') || name.includes('austin'))            return 'cota';
  if (name.includes('baku') || name.includes('azerbaijan'))            return 'baku';
  if (name.includes('abu dhabi') || name.includes('yas marina'))       return 'abudhabi';
  if (name.includes('interlagos') || name.includes('carlos pace'))     return 'interlagos';
  if (name.includes('jeddah') || name.includes('corniche'))            return 'jeddah';
  if (name.includes('zandvoort'))                                       return 'zandvoort';
  if (name.includes('villeneuve') || name.includes('montreal'))        return 'canada';
  if (name.includes('marina bay') || name.includes('singapore'))       return 'singapore';
  if (name.includes('albert park') || name.includes('melbourne'))      return 'australia';
  // Las Vegas has a massive 1.9 km straight → Baku-type
  if (name.includes('las vegas') || name.includes('strip'))            return 'baku';
  // Miami is a mixed semi-street circuit → COTA-type
  if (name.includes('miami'))                                           return 'cota';
  // Imola is very technical permanent → Hungary-type
  if (name.includes('imola') || name.includes('enzo') || name.includes('dino ferrari')) return 'hungary';
  // Mexico City stadium section → COTA-type
  if (name.includes('rodriguez') || name.includes('mexico'))           return 'cota';
  // Qatar (Losail) → Bahrain-type (both Middle East permanents)
  if (name.includes('losail') || name.includes('qatar'))               return 'bahrain';
  // Red Bull Ring is compact and fast → Zandvoort-type
  if (name.includes('red bull ring') || name.includes('spielberg'))    return 'zandvoort';
  // Barcelona is a medium-high downforce permanent → Silverstone-type
  if (name.includes('barcelona') || name.includes('catalunya'))        return 'silverstone';

  // Fallback by circuit properties
  const { circuit_type, downforce_requirement, overtaking_difficulty } = circuit;
  if (circuit_type === 'street') {
    // Low overtaking = long straights (Baku-like), high = tight (Singapore-like)
    if (overtaking_difficulty < 45) return 'baku';
    if (overtaking_difficulty < 65) return 'canada';
    return 'singapore';
  }
  if (downforce_requirement <= 30) return 'monza';
  if (overtaking_difficulty >= 75) return 'hungary';
  return 'permanent_medium';
}

const track = computed(() => TRACKS[selectTrackKey(props.circuit)]);

// ─── SVG path ref ─────────────────────────────────────────────────────────────
const pathRef = ref(null);

// ─── Animation ────────────────────────────────────────────────────────────────
const animProgress = ref(0);
const activeList   = ref([]);
const dnfList      = ref([]);

let animId = null;
let lastTs = null;
const CYCLE_MS = 7000;

function runFrame(ts) {
  if (!lastTs) lastTs = ts;
  const dt = ts - lastTs;
  lastTs = ts;

  const prev = animProgress.value;
  animProgress.value = (animProgress.value + dt / CYCLE_MS) % 1.0;

  // Detect wrap-around = leader crossed start/finish = one lap done
  if (prev > animProgress.value) {
    emit('lap-complete');
  }

  computePositions();
  animId = requestAnimationFrame(runFrame);
}

// Live lap timer: animProgress maps 0→1 to 0→estimatedLapTime seconds
const lapTimerStr = computed(() => {
  const totalSec = animProgress.value * props.estimatedLapTime;
  const m = Math.floor(totalSec / 60);
  const s = totalSec % 60;
  const sec = Math.floor(s);
  const ms  = Math.floor((s - sec) * 1000);
  return `${m}:${String(sec).padStart(2,'0')}.${String(ms).padStart(3,'0')}`;
});

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

  const total = sorted.length || 20;

  sorted.forEach((r, idx) => {
    const isDnf = r.status === 'dnf';
    const color = r.driver?.team?.color || '#888888';
    const code  = r.driver?.code || '---';

    let gapFrac;

    if (props.mode === 'qualifying') {
      const bestTime = r.q3_time || r.q2_time || r.q1_time || null;
      const poleTime = sorted[0]
        ? (sorted[0].q3_time || sorted[0].q2_time || sorted[0].q1_time || null)
        : null;

      if (bestTime !== null && poleTime !== null) {
        // Exaggerate spread so drivers visually spread across ~40% of track
        const timeDiff = bestTime - poleTime;
        gapFrac = Math.min(timeDiff / 3.5, 0.9) * 0.45;
      } else {
        // No time data (loading): spread evenly by index over 80% of track
        gapFrac = (idx / total) * 0.8;
      }
    } else {
      // Race: minimum 0.5% track per position + time-based gap
      const minFrac = (idx / total) * 0.12;
      const gapSec  = r.gap_to_leader || 0;
      const timeFrac = Math.min(gapSec / 90, 0.85) * 0.70;
      gapFrac = Math.max(minFrac, timeFrac);
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

// ─── Static markers ───────────────────────────────────────────────────────────
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
    const dx = pt1.x - pt0.x, dy = pt1.y - pt0.y;
    const dist = Math.sqrt(dx * dx + dy * dy) || 1;
    const nx = -dy / dist, ny = dx / dist;
    const pm = el.getPointAtLength(frac * len);
    return { x1: pm.x - nx * half, y1: pm.y - ny * half,
             x2: pm.x + nx * half, y2: pm.y + ny * half };
  };

  sfLine.value  = buildCrossLine(cfg.sf  || 0.02);
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
  lastTs = null;
  buildStaticMarkers();
}, { deep: true });

watch(() => props.results, () => computePositions(), { deep: true });

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
.track-svg { width: 100%; height: 100%; display: block; }

.tm-info {
  position: absolute; bottom: 10px; left: 14px;
  display: flex; flex-direction: column; gap: 1px;
  pointer-events: none;
}
.tm-circuit { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
.tm-lap { font-size: 13px; color: #aaa; }
.tm-lap strong { color: #e10600; }
.tm-timer {
  font-size: 16px;
  font-weight: 700;
  font-family: 'Courier New', monospace;
  color: #e8e8e8;
  letter-spacing: 1px;
  text-shadow: 0 0 8px rgba(225,6,0,0.5);
}

.tm-legend {
  position: absolute; bottom: 10px; right: 14px;
  display: flex; flex-direction: column; gap: 4px;
  pointer-events: none;
}
.tm-leg-item { display: flex; align-items: center; gap: 5px; }
.tm-leg-pos { font-size: 10px; color: #666; width: 12px; text-align: right; }
.tm-leg-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.tm-leg-code { font-size: 11px; font-weight: 700; color: #ccc; }
</style>
