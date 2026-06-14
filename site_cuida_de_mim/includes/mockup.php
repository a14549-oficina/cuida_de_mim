<?php // includes/mockup.php ?>
<section class="mockup-section">
    <div class="mockup-wrapper">

        <!-- Floating badges -->
        <div class="float-badge fb-left">
            <div class="float-badge-icon" style="background:#d1fae5;color:#10b981;"><i class="fas fa-check"></i></div>
            <div>
                <div class="fb-title">Toma registada</div>
                <div class="fb-sub">Paracetamol 08:00</div>
            </div>
        </div>
        <div class="float-badge fb-right">
            <div class="float-badge-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-bell"></i></div>
            <div>
                <div class="fb-title">Lembrete</div>
                <div class="fb-sub">Consulta amanhã</div>
            </div>
        </div>
        <div class="float-badge fb-bottom-left">
            <div class="float-badge-icon" style="background:#fce7f3;color:#db2777;"><i class="fas fa-weight"></i></div>
            <div>
                <div class="fb-title">IMC: Normal</div>
                <div class="fb-sub">72.5 kg · 25.09</div>
            </div>
        </div>
        <div class="float-badge fb-bottom">
            <div class="float-badge-icon" style="background:#fef3c7;color:#f59e0b;"><i class="fas fa-smile"></i></div>
            <div>
                <div class="fb-title">Humor: Ótimo 😄</div>
                <div class="fb-sub">Diário atualizado</div>
            </div>
        </div>

        <div class="mockup-frame">
            <!-- Browser bar -->
            <div class="mockup-bar">
                <div class="mockup-dot" style="background:#ff5f57;"></div>
                <div class="mockup-dot" style="background:#ffbd2e;"></div>
                <div class="mockup-dot" style="background:#28ca41;"></div>
                <div class="mockup-url">cuidademi.pt/dashboard</div>
                <div class="mockup-bar-icons">
                    <i class="fas fa-lock" style="font-size:9px;color:#94a3b8;"></i>
                </div>
            </div>

            <div class="mockup-inner">
                <!-- Sidebar (matches real app) -->
                <div class="mock-sidebar">
                    <div class="mock-logo">
                        <div class="mock-logo-icon"><i class="fas fa-heartbeat"></i></div>
                        <div>
                            <div class="mock-logo-text">Cuida de Mim</div>
                            <div class="mock-logo-sub">SAÚDE</div>
                        </div>
                    </div>
                    <div class="mock-nav-group">
                        <div class="mock-nav-label">VISÃO GERAL</div>
                        <div class="mock-nav-item active"><i class="fas fa-home"></i> Início</div>
                    </div>
                    <div class="mock-nav-group">
                        <div class="mock-nav-label">SAÚDE DIÁRIA</div>
                        <div class="mock-nav-item"><i class="fas fa-pills"></i> Tomas do Dia</div>
                        <div class="mock-nav-item"><i class="fas fa-book-medical"></i> Diário de Saúde</div>
                    </div>
                    <div class="mock-nav-group">
                        <div class="mock-nav-label">SAÚDE FÍSICA</div>
                        <div class="mock-nav-item"><i class="fas fa-weight"></i> Peso &amp; IMC</div>
                        <div class="mock-nav-item"><i class="fas fa-heartbeat"></i> Tensão Arterial</div>
                    </div>
                    <div class="mock-nav-group">
                        <div class="mock-nav-label">GESTÃO</div>
                        <div class="mock-nav-item"><i class="fas fa-capsules"></i> Medicamentos</div>
                        <div class="mock-nav-item"><i class="fas fa-calendar-check"></i> Consultas</div>
                        <div class="mock-nav-item mock-nav-badge"><i class="fas fa-bell"></i> Lembretes <span class="mock-badge">3</span></div>
                    </div>
                    <div class="mock-nav-group">
                        <div class="mock-nav-label">ANÁLISE</div>
                        <div class="mock-nav-item"><i class="fas fa-chart-line"></i> Estatísticas</div>
                        <div class="mock-nav-item"><i class="fas fa-robot"></i> Relatório IA</div>
                    </div>
                </div>

                <!-- Content (matches real dashboard) -->
                <div class="mock-content">
                    <div class="mock-topbar">
                        <span class="mock-page-title">Dashboard</span>
                        <div class="mock-topbar-icons">
                            <div class="mock-icon-btn"><i class="fas fa-bell"></i><span class="mock-notif">2</span></div>
                            <div class="mock-icon-btn signout"><i class="fas fa-sign-out-alt"></i> Sair</div>
                        </div>
                    </div>

                    <div class="mock-hero">
                        <div class="mock-hero-title">Bom dia, Simão Ribeiro!</div>
                        <div class="mock-hero-sub">Aqui está o resumo da sua saúde hoje.</div>
                        <div class="mock-stats">
                            <div class="mock-stat">
                                <div class="mock-stat-label"><i class="fas fa-pills"></i> MEDICAMENTOS</div>
                                <div class="mock-stat-val">4</div>
                                <div class="mock-stat-note">ativos</div>
                            </div>
                            <div class="mock-stat">
                                <div class="mock-stat-label"><i class="fas fa-clock"></i> TOMAS HOJE</div>
                                <div class="mock-stat-val">4/4</div>
                                <div class="mock-stat-note">em curso</div>
                            </div>
                            <div class="mock-stat">
                                <div class="mock-stat-label"><i class="fas fa-calendar"></i> CONSULTAS</div>
                                <div class="mock-stat-val">1</div>
                                <div class="mock-stat-note">próximas</div>
                            </div>
                        </div>
                    </div>

                    <div class="mock-row">
                        <div class="mock-streak">
                            <div class="mock-streak-num">2</div>
                            <div class="mock-streak-label">dias em sequência</div>
                            <div class="mock-streak-rec">Recorde: 2 dias</div>
                        </div>
                        <div class="mock-metric-card">
                            <div class="mock-metric-icon" style="color:#2563eb;"><i class="fas fa-weight"></i></div>
                            <div class="mock-metric-label">ÚLTIMO PESO</div>
                            <div class="mock-metric-val">72.5 <span>kg</span></div>
                            <div class="mock-metric-sub">IMC 25.09</div>
                        </div>
                        <div class="mock-metric-card">
                            <div class="mock-metric-icon" style="color:#ef4444;"><i class="fas fa-heartbeat"></i></div>
                            <div class="mock-metric-label">TENSÃO ARTERIAL</div>
                            <div class="mock-metric-val">120/80</div>
                            <div class="mock-metric-sub">mmHg</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
