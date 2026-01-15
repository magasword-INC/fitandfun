<?php
// Configuration des prix des abonnements
$prix_abonnements = [
    'decouverte' => [
        'titre' => 'Découverte',
        'prix' => 5,
        'unite' => '/ séance',
        'features' => [
            'Accès à 1 séance',
            'Sans engagement',
            'Accès vestiaires & douches',
            'Validité 1 mois'
        ],
        'color' => '#4CAF50',
        'btn_text' => 'Choisir'
    ],
    'mensuel' => [
        'titre' => 'Mensuel',
        'prix' => 19.99,
        'unite' => '/ mois',
        'features' => [
            'Accès illimité 7j/7',
            'Cours collectifs inclus',
            'Coaching (1h/mois)',
            'Accès application mobile'
        ],
        'color' => 'var(--secondary-color)',
        'populaire' => true,
        'btn_text' => "S'abonner"
    ],
    'annuel' => [
        'titre' => 'Annuel',
        'prix' => 199,
        'unite' => '/ an',
        'features' => [
            '2 mois offerts',
            'Accès illimité 24/7',
            'Pack bienvenue offert',
            'Report vacances (4 sem.)'
        ],
        'color' => '#333',
        'btn_text' => 'Choisir'
    ]
];
?>