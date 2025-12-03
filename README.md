# 💰 Gerenciador Financeiro

Sistema completo de gestão financeira pessoal com suporte a múltiplos usuários, pagamentos compartilhados, importação de faturas de cartão e fechamento de ciclos.

## 🚀 Funcionalidades

- ✅ **Dashboard completo** com visão de recebimentos, pagamentos e saldo
- 💳 **Importação de faturas** de cartão (Nubank, Itaú)
- 👥 **Pagamentos compartilhados** com divisão por percentual
- 📊 **Fechamento de ciclos** com cálculo automático de acertos
- 📱 **Interface responsiva** para desktop e mobile
- 🔐 **Autenticação** multi-usuário
- 💵 **Controle de recebimentos** com status de confirmação

## 📋 Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Extensões PHP: PDO, PDO_MySQL, JSON

## 🔧 Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/contas.donaire.git
cd contas.donaire
```

### 2. Configure o banco de dados

Execute o script SQL para criar o banco e as tabelas:

```bash
mysql -u root -p < database_schema.sql
```

Ou importe manualmente pelo phpMyAdmin/MySQL Workbench

### 3. Configure o sistema

Copie o arquivo de configuração de exemplo:

```bash
cp config/config.example.php config/config.php
```

Edite `config/config.php` com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'financeiro_db');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('SITE_URL', 'http://seu-dominio.com/public');
```

### 4. Permissões (Linux/Mac)

```bash
chmod 755 -R public/
chmod 644 config/config.php
```

### 5. Acesse o sistema

Navegue para `http://seu-dominio.com/public`

**Login padrão:**
- Email: `admin@financeiro.com`
- Senha: `admin123`

⚠️ **IMPORTANTE:** Altere a senha padrão após o primeiro acesso!

## 📁 Estrutura do Projeto

```
contas.donaire/
├── config/               # Configurações do sistema
│   ├── config.php       # Configurações (não versionar)
│   ├── config.example.php
│   └── Database.php     # Classe de conexão
├── src/                 # Código-fonte backend
│   ├── Auth.php         # Autenticação
│   ├── Controllers/     # Controladores
│   ├── Models/          # Modelos de dados
│   └── Services/        # Serviços de negócio
├── public/              # Arquivos públicos (web root)
│   ├── index.php        # Dashboard
│   ├── login.php        # Tela de login
│   ├── pagamentos.php   # Gestão de pagamentos
│   ├── recebimentos.php # Gestão de recebimentos
│   ├── ciclos.php       # Fechamento de ciclos
│   └── assets/          # CSS, JS, imagens
├── api/                 # Endpoints da API REST
├── database/            # Scripts SQL
└── database_schema.sql  # Schema completo do banco

```

## 🔐 Segurança

- Senhas criptografadas com `password_hash()`
- Proteção contra SQL Injection com prepared statements
- Validação de sessão em todas as páginas protegidas
- Arquivo `config.php` excluído do Git via `.gitignore`

## 🎯 Uso

### Importar Fatura de Cartão

1. Acesse **Pagamentos** → **Importar Fatura**
2. Cole o CSV do banco ou carregue o arquivo
3. Marque os itens compartilhados
4. Configure a divisão percentual
5. Confirme a importação

### Fechar Ciclo

1. Acesse **Fechamento**
2. Defina o período (ex: 01/12 a 31/12)
3. Clique em **Fechar Ciclo**
4. O sistema calcula automaticamente quem deve para quem
5. Lançamentos de acerto são criados automaticamente

### Confirmar Pagamentos/Recebimentos

- Vá em **Pagamentos** ou **Recebimentos**
- Clique no botão **⏰ A Pagar/Receber**
- Confirme quando efetuar o pagamento/recebimento
- O saldo é atualizado automaticamente

## 🤝 Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT.

## 👨‍💻 Autor

Anderson Donaire

## 📞 Suporte

Para suporte, abra uma issue no GitHub ou entre em contato.

---

⭐ Se este projeto foi útil para você, considere dar uma estrela!
