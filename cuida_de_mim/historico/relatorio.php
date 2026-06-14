<?php
$page_id = 'relatorio';
$page_title = 'Relatório IA';
require_once __DIR__ . '/../config/config.php';
$uid = user_id();

$medicamentos  = db_query('SELECT * FROM medicamentos WHERE utilizador_id=? AND ativo=1', [$uid]);
$total_meds    = count($medicamentos);
$tomas_hoje    = db_query('SELECT * FROM tomas WHERE utilizador_id=? AND data=CURDATE()', [$uid]);
$tomadas_hoje  = array_sum(array_column($tomas_hoje, 'tomado'));
$adesao_hoje   = $total_meds ? round($tomadas_hoje / $total_meds * 100) : 0;
$adesao30      = db_row('SELECT ROUND(AVG(a)*100) pct FROM (SELECT SUM(tomado)/COUNT(*) a FROM tomas WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 29 DAY) GROUP BY data) t', [$uid]);
$adesao_media  = $adesao30['pct'] ?? 0;
$diario7       = db_query('SELECT * FROM diario WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) ORDER BY data DESC', [$uid]);
$energia_media = count($diario7) ? round(array_sum(array_column($diario7, 'energia')) / count($diario7), 1) : 0;
$dor_media     = count($diario7) ? round(array_sum(array_column($diario7, 'dor'))     / count($diario7), 1) : 0;
$stock_baixo   = db_query('SELECT nome, quantidade FROM medicamentos WHERE utilizador_id=? AND ativo=1 AND quantidade<=10 ORDER BY quantidade ASC', [$uid]);
$prox_consulta = db_row('SELECT * FROM consultas WHERE utilizador_id=? AND datahora>=NOW() ORDER BY datahora ASC LIMIT 1', [$uid]);
$sintomas30    = [];
$dor_alta      = (int)(db_row('SELECT COUNT(*) c FROM diario WHERE utilizador_id=? AND dor>=7 AND data>=DATE_SUB(CURDATE(),INTERVAL 29 DAY)', [$uid])['c'] ?? 0);
$humor_row     = db_row('SELECT humor, COUNT(*) n FROM diario WHERE utilizador_id=? AND data>=DATE_SUB(CURDATE(),INTERVAL 29 DAY) GROUP BY humor ORDER BY n DESC LIMIT 1', [$uid]);
$humor_pred    = $humor_row['humor'] ?? null;

$dados_saude = json_encode([
    'adesao_hoje'   => $adesao_hoje,
    'adesao_media'  => $adesao_media,
    'energia_media' => $energia_media,
    'dor_media'     => $dor_media,
    'dor_alta'      => $dor_alta,
    'humor'         => $humor_pred,
    'total_meds'    => $total_meds,
    'diario_dias'   => count($diario7),
    'stock_baixo'   => array_map(fn($m) => $m['nome'].' ('.$m['quantidade'].'un)', $stock_baixo),
    'sintomas'      => $sintomas30,
    'prox_consulta' => $prox_consulta ? date('d/m/Y', strtotime($prox_consulta['datahora'])).' com '.($prox_consulta['medico'] ?? '') : null,
    'data'          => date('d/m/Y'),
]);

include '../includes/header.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-robot"></i> Análise da Sua Saúde</div>
    <a href="relatorio_pdf.php" class="btn btn-ghost btn-sm" target="_blank" title="Exportar relatório para PDF/impressão">
      <i class="fas fa-file-pdf" style="color:#ef4444"></i> Exportar PDF
    </a>
  </div>
  <div class="card-body">

    <! ECRÃ INICIAL >
    <div id="ecra-inicial" style="text-align:center;padding:60px 20px">
      <div style="font-size:56px;margin-bottom:20px"></div>
      <div style="font-size:20px;font-weight:800;color:var(--text);margin-bottom:10px">Gerar Relatório de Saúde</div>
      <div style="font-size:14px;color:var(--text-muted);margin-bottom:32px;max-width:380px;margin-left:auto;margin-right:auto">
        Análise automática baseada nos seus dados dos últimos 30 dias
      </div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;max-width:460px;margin:0 auto 32px">
        <div style="background:#f0f9ff;border-radius:12px;padding:14px 10px">
          <div style="font-size:22px;font-weight:800;color:#0369a1"><?php echo $adesao_media ?>%</div>
          <div style="font-size:11px;color:#0369a1;margin-top:2px">Adesão média</div>
        </div>
        <div style="background:#f0fdf4;border-radius:12px;padding:14px 10px">
          <div style="font-size:22px;font-weight:800;color:#059669"><?php echo $energia_media ?></div>
          <div style="font-size:11px;color:#059669;margin-top:2px">Energia média</div>
        </div>
        <div style="background:#fff7ed;border-radius:12px;padding:14px 10px">
          <div style="font-size:22px;font-weight:800;color:#d97706"><?php echo $dor_media ?></div>
          <div style="font-size:11px;color:#d97706;margin-top:2px">Dor média</div>
        </div>
      </div>
      <button onclick="gerarRelatorio()" class="btn btn-primary" style="padding:14px 36px;font-size:15px;font-weight:700;border-radius:12px">
        <i class="fas fa-magic"></i> Gerar Relatório
      </button>
    </div>

    <! LOADING >
    <div id="ecra-loading" style="display:none;text-align:center;padding:80px 20px">
      <div style="font-size:48px;margin-bottom:20px;animation:spin 1.5s linear infinite;display:inline-block"></div>
      <div style="font-size:16px;font-weight:700;color:var(--text);margin-bottom:8px">A analisar os seus dados...</div>
      <div style="font-size:13px;color:var(--text-muted)" id="loading-texto">A IA está a processar o seu relatório de saúde</div>
    </div>

    <! RESULTADO >
    <div id="ecra-resultado" style="display:none">
      <div style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-radius:12px;padding:20px 24px;margin-bottom:20px;border:1px solid #bae6fd;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div>
          <div style="font-size:17px;font-weight:800;color:#0369a1;margin-bottom:4px">Relatório de Saúde — <?php echo date('d/m/Y') ?></div>
          <div style="font-size:12px;color:#0369a1;opacity:.8">Análise IA baseada nos seus dados dos últimos 30 dias</div>
        </div>
        <button onclick="voltarInicio()" class="btn" style="background:#e0f2fe;color:#0369a1;border:none;font-size:12px;padding:8px 16px;border-radius:8px;cursor:pointer;font-weight:600">
          <i class="fas fa-redo"></i> Novo relatório
        </button>
      </div>
      <div class="stats-grid stats-grid-3" style="margin-bottom:20px">
        <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Adesão Hoje</div><div class="stat-card-icon icon-green"><i class="fas fa-pills"></i></div></div>
          <div class="stat-card-value"><?php echo $adesao_hoje ?>%</div><div class="stat-card-sub">medicamentos tomados</div></div>
        <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Adesão 30 dias</div><div class="stat-card-icon icon-blue"><i class="fas fa-chart-line"></i></div></div>
          <div class="stat-card-value"><?php echo $adesao_media ?>%</div><div class="stat-card-sub">média</div></div>
        <div class="stat-card"><div class="stat-card-header"><div class="stat-card-label">Energia Média</div><div class="stat-card-icon icon-yellow"><i class="fas fa-bolt"></i></div></div>
          <div class="stat-card-value"><?php echo $energia_media ?></div><div class="stat-card-sub">últimos 7 dias</div></div>
      </div>
      <div id="analise-ia-container"></div>
    </div>

  </div>
</div>

<style>
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.ia-secao { border-radius:12px; padding:18px 20px; margin-bottom:14px; }
.ia-secao-titulo { font-size:14px; font-weight:800; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
.ia-item { display:flex; gap:10px; align-items:flex-start; font-size:13.5px; line-height:1.5; padding:4px 0; }
</style>

<script>
const dadosSaude = <?php echo $dados_saude ?>;

async function gerarRelatorio() {
  document.getElementById('ecra-inicial').style.display   = 'none';
  document.getElementById('ecra-loading').style.display   = 'block';
  document.getElementById('ecra-resultado').style.display = 'none';

  const textos = [
    'A IA está a processar o seu relatório de saúde',
    'A analisar padrões de adesão a medicamentos...',
    'A verificar registos do diário de saúde...',
    'A preparar recomendações personalizadas...',
  ];
  let ti = 0;
  const iv = setInterval(() => {
    ti = (ti + 1) % textos.length;
    const el = document.getElementById('loading-texto');
    if (el) el.textContent = textos[ti];
  }, 2000);

  try {
    const prompt = `És um assistente de saúde da aplicação "Cuida de Mim".
Analisa estes dados de saúde dos últimos 30 dias e responde APENAS com JSON válido, sem markdown nem texto extra:

Adesão hoje: ${dadosSaude.adesao_hoje}%
Adesão média 30 dias: ${dadosSaude.adesao_media}%
Energia média (1-10): ${dadosSaude.energia_media}
Dor média (1-10): ${dadosSaude.dor_media}
Episódios dor alta (>=7): ${dadosSaude.dor_alta}
Humor predominante: ${dadosSaude.humor || 'sem dados'}
Medicamentos ativos: ${dadosSaude.total_meds}
Dias com diário (últimos 7): ${dadosSaude.diario_dias}
Stock baixo: ${dadosSaude.stock_baixo.length ? dadosSaude.stock_baixo.join(', ') : 'nenhum'}
Sintomas recorrentes: ${dadosSaude.sintomas.length ? dadosSaude.sintomas.join(', ') : 'nenhum'}
Próxima consulta: ${dadosSaude.prox_consulta || 'não agendada'}

JSON exato a devolver:
{"resumo":"string","positivos":["string"],"melhorar":["string"],"recomendacoes":[{"titulo":"string","descricao":"string"}],"nota_medico":"string ou null"}`;

    const res  = await fetch('https://api.anthropic.com/v1/messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 1000,
        messages: [{ role: 'user', content: prompt }]
      })
    });
    const data  = await res.json();
    const texto = data.content?.find(b => b.type === 'text')?.text || '';
    const clean = texto.replace(/```json|```/g, '').trim();
    const r     = JSON.parse(clean);
    clearInterval(iv);
    renderRelatorio(r);
  } catch(err) {
    clearInterval(iv);
    renderRelatorioLocal();
  }
}

function renderRelatorio(r) {
  let html = '';

  html += `<div class="ia-secao" style="background:#f0f9ff;border:1px solid #bae6fd">
    <div class="ia-secao-titulo" style="color:#0369a1"><span style="font-size:18px"></span> Análise IA</div>
    <p style="font-size:13.5px;color:#0c4a6e;margin:0;line-height:1.6">${r.resumo}</p>
  </div>`;

  if (r.positivos?.length) {
    html += `<div class="ia-secao" style="background:#f0fdf4;border:1px solid #bbf7d0">
      <div class="ia-secao-titulo" style="color:#059669"><span style="font-size:18px"></span> Pontos Positivos</div>
      <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px">
        ${r.positivos.map(p => `<li class="ia-item"><span style="color:#10b981;flex-shrink:0">●</span><span>${p}</span></li>`).join('')}
      </ul></div>`;
  }

  if (r.melhorar?.length) {
    html += `<div class="ia-secao" style="background:#fffbeb;border:1px solid #fde68a">
      <div class="ia-secao-titulo" style="color:#b45309"><span style="font-size:18px">⚠</span> Pontos a Melhorar</div>
      <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px">
        ${r.melhorar.map(m => `<li class="ia-item"><span style="color:#f59e0b;flex-shrink:0">●</span><span>${m}</span></li>`).join('')}
      </ul></div>`;
  }

  if (r.recomendacoes?.length) {
    html += `<div class="ia-secao" style="background:#fdf4ff;border:1px solid #e9d5ff">
      <div class="ia-secao-titulo" style="color:#7c3aed"><span style="font-size:18px"></span> Recomendações</div>
      <div style="display:flex;flex-direction:column;gap:10px">
        ${r.recomendacoes.map(rec => `<div style="background:#fff;border-radius:8px;padding:12px 14px;border-left:3px solid #7c3aed">
          <div style="font-weight:700;font-size:13px;margin-bottom:3px">${rec.titulo}</div>
          <div style="font-size:12.5px;color:var(--text-muted)">${rec.descricao}</div>
        </div>`).join('')}
      </div></div>`;
  }

  if (r.nota_medico) {
    html += `<div class="ia-secao" style="background:#fff1f2;border:1px solid #fecdd3">
      <div class="ia-secao-titulo" style="color:#be123c"><span style="font-size:18px"></span> Para partilhar com o seu médico</div>
      <p style="font-size:13.5px;color:#9f1239;margin:0;line-height:1.6">${r.nota_medico}</p>
    </div>`;
  }

  document.getElementById('analise-ia-container').innerHTML = html;
  document.getElementById('ecra-loading').style.display   = 'none';
  document.getElementById('ecra-resultado').style.display = 'block';
}

function renderRelatorioLocal() {
  const r = {
    resumo: `Adesão média de ${dadosSaude.adesao_media}%, energia de ${dadosSaude.energia_media}/10 e dor de ${dadosSaude.dor_media}/10 nos últimos 7 dias.`,
    positivos: [], melhorar: [],
    recomendacoes: [
      { titulo: ' Registe o diário diariamente', descricao: 'Registos consistentes permitem análises mais precisas.' },
      { titulo: ' Configure lembretes', descricao: 'Use os lembretes para não se esquecer das tomas.' }
    ],
    nota_medico: dadosSaude.dor_alta > 0 ? `Foram registados ${dadosSaude.dor_alta} episódio(s) de dor elevada (≥7/10) no último mês.` : null
  };
  if (dadosSaude.adesao_media >= 80) r.positivos.push(`Adesão de ${dadosSaude.adesao_media}% — excelente!`);
  if (dadosSaude.dor_media < 3)      r.positivos.push(`Dor média baixa (${dadosSaude.dor_media}/10) — sem picos preocupantes.`);
  if (dadosSaude.diario_dias >= 5)   r.positivos.push(`${dadosSaude.diario_dias} registos no diário esta semana — ótima consistência.`);
  if (!r.positivos.length)           r.positivos.push('Está a acompanhar ativamente a sua saúde — continue assim!');
  if (dadosSaude.stock_baixo.length) r.melhorar.push(`Stock baixo: ${dadosSaude.stock_baixo.join(', ')} — renove em breve.`);
  if (dadosSaude.adesao_media < 80 && dadosSaude.adesao_media > 0) r.melhorar.push(`Adesão de ${dadosSaude.adesao_media}% — tente melhorar para acima de 80%.`);
  if (!r.melhorar.length)            r.melhorar.push('Sem pontos críticos identificados — continue com os bons hábitos!');
  renderRelatorio(r);
}

function voltarInicio() {
  document.getElementById('ecra-resultado').style.display = 'none';
  document.getElementById('ecra-loading').style.display   = 'none';
  document.getElementById('ecra-inicial').style.display   = 'block';
}
</script>

<?php include '../includes/footer.php'; ?>
