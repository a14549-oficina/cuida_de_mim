<?php
/*Cuida de Mim — Gerar e descarregar PDF diretamente (FPDF)*/

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../libs/fpdf.php';

$uid = user_id();
$u   = user();

// Período
$dias  = (int)($_GET['dias'] ?? 30);
$dias  = in_array($dias, [7, 30, 90]) ? $dias : 30;
$desde = date('Y-m-d', strtotime("-{$dias} days"));

// Dados da BD 
$medicamentos  = db_query('SELECT * FROM medicamentos WHERE utilizador_id=? AND ativo=1', [$uid]);
$tomas_periodo = db_query(
    'SELECT data, SUM(tomado) tomados, COUNT(*) total
     FROM tomas WHERE utilizador_id=? AND data>=? GROUP BY data ORDER BY data ASC',
    [$uid, $desde]
);
$adesao_pct = 0;
if ($tomas_periodo) {
    $tot = array_sum(array_column($tomas_periodo, 'total'));
    $tom = array_sum(array_column($tomas_periodo, 'tomados'));
    $adesao_pct = $tot > 0 ? round($tom / $tot * 100) : 0;
}
$consultas    = db_query('SELECT * FROM consultas WHERE utilizador_id=? AND datahora>=? ORDER BY datahora ASC', [$uid, $desde.' 00:00:00']);
$diario       = db_query('SELECT * FROM diario WHERE utilizador_id=? AND data>=? ORDER BY data ASC', [$uid, $desde]);
$pesos        = db_query('SELECT * FROM peso_historico WHERE utilizador_id=? AND data>=? ORDER BY data ASC', [$uid, $desde]);
$tensoes      = db_query('SELECT * FROM tensao_arterial WHERE utilizador_id=? AND data>=? ORDER BY data ASC, hora ASC', [$uid, $desde]);
$ultimo_peso  = $pesos  ? end($pesos)   : null;
$primeiro_peso = $pesos ? $pesos[0]     : null;
$diff_peso    = ($ultimo_peso && $primeiro_peso && $primeiro_peso['peso'] != $ultimo_peso['peso'])
    ? round($ultimo_peso['peso'] - $primeiro_peso['peso'], 1) : null;
$energia_media = $diario ? round(array_sum(array_column($diario, 'energia')) / count($diario), 1) : null;
$dor_media     = $diario ? round(array_sum(array_column($diario, 'dor'))     / count($diario), 1) : null;
$sintomas      = [];

//  Helper: texto sem acentos para FPDF (ISO-8859-1) 
function txt(string $s): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
}

//  Classe PDF personalizada 
class RelatorioPDF extends FPDF {
    public string $nomePaciente = '';
    public string $emailPaciente = '';
    public string $periodo = '';
    public string $geradoEm = '';

    function Header() {
        // Fundo azul no topo
        $this->SetFillColor(37, 99, 235);
        $this->Rect(0, 0, 210, 22, 'F');

        // Logo / título
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(14, 5);
        $this->Cell(80, 12, txt('Cuida de Mim'), 0, 0, 'L');

        // Info do paciente (lado direito)
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetXY(110, 4);
        $this->Cell(86, 5, txt($this->nomePaciente), 0, 2, 'R');
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(220, 230, 255);
        $this->Cell(86, 4, txt($this->emailPaciente), 0, 2, 'R');
        $this->Cell(86, 4, txt($this->periodo), 0, 2, 'R');
        $this->Cell(86, 4, txt($this->geradoEm), 0, 0, 'R');

        $this->SetTextColor(30, 41, 59);
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(0, 10, txt('Cuida de Mim — Documento gerado automaticamente. Consulte sempre o seu médico.'), 0, 0, 'C');
    }

    // Título de secção com barra azul
    function SectionTitle(string $title) {
        $this->Ln(4);
        $this->SetFillColor(241, 245, 249);
        $this->SetDrawColor(37, 99, 235);
        $this->SetLineWidth(0.8);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(15, 23, 42);
        $this->SetX(14);
        // Barra lateral azul
        $this->SetFillColor(37, 99, 235);
        $this->Rect(14, $this->GetY(), 1.5, 8, 'F');
        $this->SetFillColor(241, 245, 249);
        $this->SetX(16);
        $this->Cell(180, 8, txt('  '.$title), 0, 1, 'L', true);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(226, 232, 240);
        $this->Ln(2);
    }

    // Card de métrica (valor + label)
    function MetricCard(float $x, float $y, float $w, float $h, string $value, string $label, array $color = [15,23,42]) {
        $this->SetFillColor(248, 250, 252);
        $this->SetDrawColor(226, 232, 240);
        $this->RoundedRect($x, $y, $w, $h, 2, 'DF');
        $this->SetFont('Helvetica', 'B', 16);
        $this->SetTextColor($color[0], $color[1], $color[2]);
        $this->SetXY($x, $y + 4);
        $this->Cell($w, 8, txt($value), 0, 0, 'C');
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(100, 116, 139);
        $this->SetXY($x, $y + 13);
        $this->Cell($w, 4, txt(strtoupper($label)), 0, 0, 'C');
        $this->SetTextColor(15, 23, 42);
    }

    // Canto arredondado
    function RoundedRect(float $x, float $y, float $w, float $h, float $r, string $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if ($style === 'F') $op = 'f';
        elseif ($style === 'FD' || $style === 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $xc = $x+$w-$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));
        $this->_Arc($xc+$r*$MyArc, $yc-$r, $xc+$r, $yc-$r*$MyArc, $xc+$r, $yc);
        $xc = $x+$w-$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k));
        $this->_Arc($xc+$r, $yc+$r*$MyArc, $xc+$r*$MyArc, $yc+$r, $xc, $yc+$r);
        $xc = $x+$r; $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k, ($hp-($y+$h))*$k));
        $this->_Arc($xc-$r*$MyArc, $yc+$r, $xc-$r, $yc+$r*$MyArc, $xc-$r, $yc);
        $xc = $x+$r; $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$yc)*$k));
        $this->_Arc($xc-$r, $yc-$r*$MyArc, $xc-$r*$MyArc, $yc-$r, $xc, $yc-$r);
        $this->_out($op);
    }
    function _Arc(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c', $x1*$this->k, ($h-$y1)*$this->k, $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }

    // Cabeçalho de tabela
    function TableHeader(array $cols, array $widths) {
        $this->SetFillColor(241, 245, 249);
        $this->SetDrawColor(226, 232, 240);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(71, 85, 105);
        $this->SetX(14);
        foreach ($cols as $i => $col) {
            $this->Cell($widths[$i], 7, txt(strtoupper($col)), 1, 0, 'L', true);
        }
        $this->Ln();
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(15, 23, 42);
    }

    // Linha de tabela 
    function TableRow(array $vals, array $widths, int $i) {
        $this->SetFillColor($i % 2 === 0 ? 255 : 248, $i % 2 === 0 ? 255 : 250, $i % 2 === 0 ? 255 : 252);
        $this->SetDrawColor(241, 245, 249);
        $this->SetX(14);
        foreach ($vals as $j => $val) {
            $this->Cell($widths[$j], 7, txt((string)$val), 1, 0, 'L', true);
        }
        $this->Ln();
    }
}

// Montar PDF 
$pdf = new RelatorioPDF('P', 'mm', 'A4');
$pdf->nomePaciente  = $u['nome'];
$pdf->emailPaciente = $u['email'];
$pdf->periodo       = 'Período: ' . date('d/m/Y', strtotime($desde)) . ' – ' . date('d/m/Y');
$pdf->geradoEm      = 'Gerado em: ' . date('d/m/Y \à\s H:i') . 'h';
$pdf->SetMargins(14, 28, 14);
$pdf->SetAutoPageBreak(true, 16);
$pdf->AddPage();

// RESUMO DO PERÍODO 
$pdf->SectionTitle('Resumo do Período');

$y = $pdf->GetY();
$cardW = 43; $cardH = 22; $gap = 3; $startX = 14;

// Cor da adesão
$cor_ades = $adesao_pct >= 80 ? [5,150,105] : ($adesao_pct >= 50 ? [217,119,6] : [220,38,38]);
$pdf->MetricCard($startX,            $y, $cardW, $cardH, $adesao_pct.'%',        'Adesão medicação', $cor_ades);
$pdf->MetricCard($startX+$cardW+$gap,$y, $cardW, $cardH, (string)count($medicamentos), 'Medicamentos ativos');
$pdf->MetricCard($startX+($cardW+$gap)*2, $y, $cardW, $cardH, (string)count($consultas), 'Consultas');
$pdf->MetricCard($startX+($cardW+$gap)*3, $y, $cardW, $cardH, (string)count($sintomas),  'Sintomas registados');

$pdf->SetY($y + $cardH + 4);

if ($energia_media !== null) {
    $y2 = $pdf->GetY();
    $w2 = ($cardW * 2 + $gap);
    $pdf->MetricCard($startX,       $y2, $w2, $cardH, $energia_media.'/10', 'Energia média');
    $pdf->MetricCard($startX+$w2+$gap, $y2, $w2, $cardH, $dor_media.'/10',   'Dor média');
    $pdf->SetY($y2 + $cardH + 4);
}

// MEDICAMENTOS 
if ($medicamentos) {
    $pdf->SectionTitle('Medicamentos Atuais');
    $pdf->TableHeader(['Medicamento','Dosagem','Forma','Horário','Frequência'], [50,30,30,25,47]);
    foreach ($medicamentos as $i => $m) {
        $pdf->TableRow([
            $m['nome'],
            $m['dosagem'] ?: '—',
            $m['forma'],
            substr($m['horario'], 0, 5),
            $m['frequencia'],
        ], [50,30,30,25,47], $i);
    }
    $pdf->Ln(2);
}

// DADOS FÍSICOS 
if ($pesos || $tensoes) {
    $pdf->SectionTitle('Dados Físicos');
    $y3 = $pdf->GetY();

    if ($ultimo_peso) {
        $pdf->MetricCard($startX, $y3, $cardW, $cardH, number_format($ultimo_peso['peso'],1).' kg', 'Peso atual');
        if ($ultimo_peso['imc']) {
            $pdf->MetricCard($startX+$cardW+$gap, $y3, $cardW, $cardH, (string)$ultimo_peso['imc'], 'IMC');
        }
        if ($diff_peso !== null) {
            $cor_diff = $diff_peso < 0 ? [5,150,105] : ($diff_peso > 0 ? [220,38,38] : [100,116,139]);
            $pdf->MetricCard($startX+($cardW+$gap)*2, $y3, $cardW, $cardH, ($diff_peso > 0 ? '+' : '').number_format($diff_peso,1).' kg', 'Variação no período', $cor_diff);
        }
        $pdf->SetY($y3 + $cardH + 4);
    }

    if ($tensoes) {
        $ult_t = end($tensoes);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(30,41,59);
        $pdf->SetX(14);
        $pdf->Cell(0, 6, txt('Última tensão arterial: '.$ult_t['sistolica'].'/'.$ult_t['diastolica'].' mmHg'.($ult_t['pulsacao'] ? ' · '.$ult_t['pulsacao'].' bpm' : '')), 0, 1);
        $pdf->Ln(2);
    }
}

// CONSULTAS 
if ($consultas) {
    $pdf->SectionTitle('Consultas no Período');
    $pdf->TableHeader(['Data','Médico','Especialidade','Local'], [28,50,45,59]);
    foreach ($consultas as $i => $c) {
        $pdf->TableRow([
            date('d/m/Y', strtotime($c['datahora'])),
            $c['medico'],
            $c['especialidade'],
            $c['local'] ?: '—',
        ], [28,50,45,59], $i);
    }
    $pdf->Ln(2);
}

// SINTOMAS 
if ($sintomas) {
    $pdf->SectionTitle('Sintomas Registados');
    $pdf->TableHeader(['Data','Sintoma'], [35,147]);
    foreach (array_slice($sintomas, 0, 25) as $i => $s) {
        $pdf->TableRow([
            date('d/m/Y', strtotime($s['data'])),
            $s['tipo'],
        ], [35,147], $i);
    }
}

// Output: forçar download 
$nome_ficheiro = 'Relatorio_Saude_' . date('Y-m-d') . '_' . $dias . 'dias.pdf';
$pdf->Output('D', $nome_ficheiro); // D = Download direto
exit;
