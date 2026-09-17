<?php

return [
    'maintenance' => [
        'maintenance' => 'Underhåll',
        'behavior' => [
            'options' => [
                'skip_alerts' => 'Hoppa över larm',
                'mute_alerts' => 'Tysta larm',
                'run_alerts' => 'Kör larm',
            ],
            'tooltip' => '- Hoppa över larm: Inga nya larm skapas och befintliga larm löses inte.
        - Tysta larm: Larm skapas och löses som vanligt, men alla aviseringar till användare, till exempel e-post, undertrycks.
        - Kör larm: Larm körs som vanligt och användarna aviseras. Det här alternativet gör underhållet rent kosmetiskt.',
        ],
        'title' => 'Titel',
    ],
];
