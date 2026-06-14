# Cuida de Mim — Aplicação Web de Gestão de Saúde Pessoal

> Aplicação web desenvolvida em PHP e MySQL para monitorização e gestão da saúde pessoal.

---

## Descrição

A **Cuida de Mim** é uma plataforma web que permite ao utilizador acompanhar a sua saúde diária de forma organizada e simples. Inclui gestão de medicamentos, registo de tensão arterial e peso, agendamento de consultas, diário de saúde, estatísticas, relatórios PDF e notificações por WhatsApp.

---

## Funcionalidades

- **Medicamentos** — registo, tomas diárias, controlo de stock
- **Peso & IMC** — histórico e gráfico de evolução
- **Tensão Arterial** — medições com classificação automática
- **Consultas** — agendamento e lembretes
- **Diário de Saúde** — humor, energia, dor e notas
- **Estatísticas** — gráficos de adesão, peso e tensão
- **Relatórios PDF** — exportação para 7, 30 ou 90 dias
- **Lembretes** — notificações automáticas por WhatsApp (Twilio)
- **Modo Cuidador** — acesso controlado por terceiros
- **Calendário** — vista mensal de eventos de saúde

---

## Tecnologias

| Tecnologia | Utilização |
|-----------|-----------|
| PHP 8.2+ | Linguagem de servidor |
| MySQL 8.0+ | Base de dados |
| PDO | Acesso seguro à BD |
| Chart.js | Gráficos interativos |
| FPDF | Geração de PDF |
| Twilio API | Notificações WhatsApp |
| CSS3 / JS | Interface responsiva |

---

## Requisitos

- [XAMPP](https://www.apachefriends.org) (Apache + PHP 8.0+ + MySQL 8.0+)
- Browser moderno (Chrome, Firefox, Edge)

---

## Instalação

### 1. Copiar ficheiros
```
Extrair o ZIP e copiar a pasta para:
C:\xampp\htdocs\cuida_de_mim\
```

### 2. Criar a base de dados
```
1. Abrir http://localhost/phpmyadmin
2. Criar base de dados: cuida_de_mim
3. Importar o ficheiro: cuida_de_mim.sql
```

### 3. Iniciar o XAMPP
```
Abrir o XAMPP Control Panel
Iniciar: Apache + MySQL
```

### 4. Aceder à aplicação
```
http://localhost/cuida_de_mim/
```

---

## Segurança

- PDO com Prepared Statements (proteção SQL Injection)
- Tokens CSRF em todos os formulários
- Hashing de passwords com bcrypt
- Rate limiting no login (5 tentativas / 5 minutos)
- Validação e sanitização de todos os inputs

---

## Estrutura do Projeto

```
cuida_de_mim/
├── config/          # Configuração central (BD, CSRF, sessão)
├── css/             # Folha de estilos principal
├── includes/        # Header, footer, JavaScript partilhado
├── medicamentos/    # Gestão de medicamentos e tomas
├── saude/           # Peso & IMC e tensão arterial
├── consultas/       # Agendamento de consultas
├── diario/          # Diário de saúde pessoal
├── historico/       # Calendário, relatórios e PDF
├── cuidador/        # Modo cuidador
├── cron/            # Lembretes automáticos (Twilio)
├── libs/            # Biblioteca FPDF
├── dashboard.php    # Painel principal
├── estatisticas.php # Gráficos e estatísticas
├── lembretes.php    # Gestão de lembretes
└── configuracoes.php# Preferências do utilizador
```

---

## Autor

**Simão Rafael Pereira Ribeiro**  
Técnico/a de Gestão e Programação de Sistemas Informáticos  
2025/2026
