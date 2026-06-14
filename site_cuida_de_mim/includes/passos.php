<?php
$steps = [
    [
        'num'   => '01',
        'icon'  => 'fas fa-user-plus',
        'title' => 'Crie a sua conta',
        'desc'  => 'Registe-se gratuitamente com o seu nome, email e password. Leva menos de um minuto.',
    ],
    [
        'num'   => '02',
        'icon'  => 'fas fa-notes-medical',
        'title' => 'Adicione a sua informação',
        'desc'  => 'Adicione medicamentos, consultas, peso e tensão. A interface guia-o em cada passo.',
    ],
    [
        'num'   => '03',
        'icon'  => 'fas fa-chart-line',
        'title' => 'Acompanhe a sua evolução',
        'desc'  => 'Use o diário, gráficos, relatório IA e mantenha-se sempre organizado na sua saúde pessoal.',
    ],
];
?>

<section class="steps-section" id="como-funciona">
    <div class="container">
        <div class="section-label light">Como funciona</div>
        <h2 class="section-title" style="color:white;">Em 3 passos está pronto</h2>
        <p class="section-sub" style="color:#94a3b8;">Simples, rápido e sem complicações.</p>

        <div class="steps-grid">
            <?php foreach ($steps as $i => $step): ?>
            <div class="step-card">
                <div class="step-num-icon">
                    <div class="step-num"><?= $step['num'] ?></div>
                    <div class="step-icon-wrap"><i class="<?= $step['icon'] ?>"></i></div>
                </div>
                <div class="step-title"><?= htmlspecialchars($step['title']) ?></div>
                <div class="step-desc"><?= htmlspecialchars($step['desc']) ?></div>
                <?php if ($i < count($steps) - 1): ?>
                <div class="step-connector"><i class="fas fa-arrow-right"></i></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
