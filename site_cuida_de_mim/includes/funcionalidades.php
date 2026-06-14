<?php
$features = [
    [
        'icon'  => 'fas fa-pills',
        'bg'    => '#dbeafe',
        'color' => '#2563eb',
        'title' => 'Gestão de Medicamentos',
        'desc'  => 'Registe medicamentos com dosagem, horário e frequência. Receba alertas e nunca mais falhe uma toma.',
        'tag'   => 'Essencial',
    ],
    [
        'icon'  => 'fas fa-weight',
        'bg'    => '#d1fae5',
        'color' => '#10b981',
        'title' => 'Peso & IMC',
        'desc'  => 'Acompanhe a evolução do peso e o índice de massa corporal com gráficos detalhados ao longo do tempo.',
        'tag'   => 'Novo',
    ],
    [
        'icon'  => 'fas fa-heartbeat',
        'bg'    => '#fce7f3',
        'color' => '#db2777',
        'title' => 'Tensão Arterial',
        'desc'  => 'Registe sistólica, diastólica e pulsação. Detete padrões e partilhe o histórico com o seu médico.',
        'tag'   => 'Novo',
    ],
    [
        'icon'  => 'fas fa-bell',
        'bg'    => '#fef3c7',
        'color' => '#f59e0b',
        'title' => 'Lembretes Inteligentes',
        'desc'  => 'Configure alertas personalizados via WhatsApp para medicação, consultas e registos de saúde.',
        'tag'   => 'Novo',
    ],
    [
        'icon'  => 'fas fa-robot',
        'bg'    => '#ede9fe',
        'color' => '#7c3aed',
        'title' => 'Relatório IA',
        'desc'  => 'Análise automática dos seus dados de saúde com sugestões e alertas gerados por inteligência artificial.',
        'tag'   => 'IA',
    ],
    [
        'icon'  => 'fas fa-calendar-check',
        'bg'    => '#fff7ed',
        'color' => '#ea580c',
        'title' => 'Consultas Médicas',
        'desc'  => 'Agende consultas com especialidade, local e hora. Tenha todas as suas consultas sempre à mão.',
        'tag'   => '',
    ],
    [
        'icon'  => 'fas fa-book-medical',
        'bg'    => '#f0fdf4',
        'color' => '#16a34a',
        'title' => 'Diário de Saúde',
        'desc'  => 'Registe diariamente humor, energia e nível de dor. Acompanhe padrões e partilhe com profissionais.',
        'tag'   => '',
    ],
    [
        'icon'  => 'fas fa-chart-line',
        'bg'    => '#f8faff',
        'color' => '#2563eb',
        'title' => 'Estatísticas',
        'desc'  => 'Gráficos detalhados de adesão à medicação, evolução do peso, tensão e energia ao longo do tempo.',
        'tag'   => '',
    ],
    [
        'icon'  => 'fas fa-users',
        'bg'    => '#fdf2f8',
        'color' => '#9d174d',
        'title' => 'Modo Cuidador',
        'desc'  => 'Acompanhe a saúde de um familiar ou dependente, com acesso controlado aos dados e notificações.',
        'tag'   => '',
    ],
];
?>

<section class="section" id="funcionalidades">
    <div class="container">
        <div class="section-label"><i class="fas fa-sparkles"></i> Funcionalidades</div>
        <h2 class="section-title">Tudo o que precisa<br>para cuidar da sua saúde</h2>
        <p class="section-sub">Uma plataforma completa, simples e acessível, do controlo diário da medicação à análise por inteligência artificial.</p>

        <div class="features-grid">
            <?php foreach ($features as $f): ?>
            <div class="feature-card">
                <?php if ($f['tag']): ?>
                <div class="feature-tag <?= strtolower($f['tag']) === 'novo' ? 'tag-green' : (strtolower($f['tag']) === 'ia' ? 'tag-purple' : 'tag-blue') ?>">
                    <?= htmlspecialchars($f['tag']) ?>
                </div>
                <?php endif; ?>
                <div class="feature-icon" style="background:<?= $f['bg'] ?>;color:<?= $f['color'] ?>;">
                    <i class="<?= $f['icon'] ?>"></i>
                </div>
                <div class="feature-title"><?= htmlspecialchars($f['title']) ?></div>
                <div class="feature-desc"><?= htmlspecialchars($f['desc']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
