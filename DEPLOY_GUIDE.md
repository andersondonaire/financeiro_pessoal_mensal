# 🚀 GUIA COMPLETO: GitHub + Codespaces + Deploy Automático

## 📌 PARTE 1: Configurar GitHub

### 1.1 Criar Repositório no GitHub

1. Acesse [github.com](https://github.com) e faça login
2. Clique em **New repository** (botão verde)
3. Configure:
   - **Nome:** `contas.donaire`
   - **Descrição:** Gerenciador Financeiro com pagamentos compartilhados
   - **Visibilidade:** Private (recomendado) ou Public
   - **NÃO** marque "Initialize with README" (já temos)
4. Clique em **Create repository**

### 1.2 Conectar seu projeto local ao GitHub

Abra o PowerShell na pasta do projeto e execute:

```powershell
cd d:\BKSITES\contas.donaire

# Inicializar Git (se ainda não foi)
git init

# Adicionar todos os arquivos
git add .

# Primeiro commit
git commit -m "Initial commit - Sistema completo de gestão financeira"

# Adicionar remote do GitHub (substitua SEU-USUARIO)
git remote add origin https://github.com/SEU-USUARIO/contas.donaire.git

# Enviar para o GitHub
git branch -M main
git push -u origin main
```

**Autenticação:** Quando pedir credenciais, use seu **Personal Access Token** (não a senha):
1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Generate new token → Marque `repo` → Generate
3. Copie o token e use como senha

---

## 📌 PARTE 2: Configurar GitHub Codespaces

### 2.1 Criar Codespace

1. No seu repositório no GitHub, clique em **Code** → **Codespaces**
2. Clique em **Create codespace on main**
3. Aguarde a criação (1-2 minutos)

### 2.2 Configurar Codespace para desenvolvimento

Crie o arquivo `.devcontainer/devcontainer.json`:

```json
{
  "name": "Gerenciador Financeiro",
  "image": "mcr.microsoft.com/devcontainers/php:7.4",
  "features": {
    "ghcr.io/devcontainers/features/node:1": {},
    "ghcr.io/devcontainers/features/php:1": {
      "version": "7.4"
    }
  },
  "forwardPorts": [3030, 3306],
  "postCreateCommand": "composer install || true",
  "customizations": {
    "vscode": {
      "extensions": [
        "bmewburn.vscode-intelephense-client",
        "ms-azuretools.vscode-docker"
      ]
    }
  }
}
```

### 2.3 Usar Codespace no VS Code Local

1. Instale a extensão **GitHub Codespaces** no VS Code
2. Ctrl+Shift+P → "Codespaces: Connect to Codespace"
3. Selecione seu codespace
4. Trabalhe normalmente como se fosse local!

---

## 📌 PARTE 3: Deploy Automático para Produção

### 3.1 Configurar Secrets no GitHub

1. No repositório, vá em **Settings** → **Secrets and variables** → **Actions**
2. Clique em **New repository secret** e adicione:

**FTP_SERVER:**
```
ftp.seudominio.com.br
```

**FTP_USERNAME:**
```
seu_usuario@seudominio.com.br
```

**FTP_PASSWORD:**
```
sua_senha_ftp
```

### 3.2 Arquivo de deploy já foi criado!

O arquivo `.github/workflows/deploy.yml` já está configurado e fará:

✅ Deploy automático a cada push na branch `main`
✅ Exclui arquivos sensíveis (config.php, .sql, .git)
✅ Envia apenas o necessário para produção

### 3.3 Primeiro Deploy

```powershell
# Faça qualquer alteração
git add .
git commit -m "feat: configurar deploy automático"
git push

# Acompanhe o deploy:
# GitHub → Actions → Deploy to Production
```

---

## 📌 PARTE 4: Workflow de Desenvolvimento

### 4.1 Trabalhar em Features

```powershell
# Criar branch para nova feature
git checkout -b feature/nome-da-feature

# Fazer alterações...
git add .
git commit -m "feat: descrição da alteração"

# Enviar branch
git push origin feature/nome-da-feature

# No GitHub: Criar Pull Request → Merge → Deploy automático!
```

### 4.2 Fluxo Recomendado

```
Desenvolver Local → Commit → Push → GitHub
                                       ↓
                                  Pull Request
                                       ↓
                              Review + Merge to main
                                       ↓
                            🚀 Deploy Automático!
```

---

## 📌 PARTE 5: Configurar Servidor de Produção

### 5.1 No seu servidor (primeira vez)

```bash
# 1. Importar banco de dados
mysql -u usuario -p < database_schema.sql

# 2. Criar config.php de produção
cp config/config.example.php config/config.php
nano config/config.php
```

Edite com dados de produção:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'financeiro_producao');
define('DB_USER', 'usuario_producao');
define('DB_PASS', 'senha_segura');
define('SITE_URL', 'https://seudominio.com.br');
```

### 5.2 Permissões Linux

```bash
chmod 755 -R public/
chmod 644 config/config.php
chown -R www-data:www-data *
```

---

## 📌 PARTE 6: Comandos Git Úteis

```powershell
# Ver status
git status

# Ver histórico
git log --oneline

# Desfazer alterações não commitadas
git checkout -- arquivo.php

# Voltar para branch main
git checkout main

# Atualizar branch local
git pull origin main

# Ver branches
git branch -a

# Deletar branch local
git branch -d feature/nome

# Deletar branch remota
git push origin --delete feature/nome
```

---

## 📌 PARTE 7: Troubleshooting

### Erro: "Authentication failed"
**Solução:** Use Personal Access Token ao invés da senha

### Erro: "Permission denied"
**Solução:** 
```powershell
git remote set-url origin https://SEU-TOKEN@github.com/SEU-USUARIO/contas.donaire.git
```

### Deploy falhou
**Solução:** 
1. GitHub → Actions → Ver logs do erro
2. Verificar credenciais FTP nos Secrets
3. Testar conexão FTP manualmente

### Config.php não funciona em produção
**Solução:** Arquivo está no .gitignore (correto!). Crie manualmente no servidor.

---

## 📌 PARTE 8: Boas Práticas

### ✅ Commits Semânticos

```
feat: nova funcionalidade
fix: correção de bug
docs: atualização de documentação
style: formatação de código
refactor: refatoração
test: adicionar testes
chore: tarefas de manutenção
```

### ✅ Nunca Commitar

- ❌ config/config.php
- ❌ Senhas ou tokens
- ❌ Arquivos .env
- ❌ node_modules/
- ❌ vendor/
- ❌ Arquivos .sql com dados sensíveis

### ✅ Sempre Commitar

- ✅ config/config.example.php
- ✅ README.md
- ✅ database_schema.sql (sem dados)
- ✅ Todo código fonte

---

## 🎯 Checklist Final

- [ ] Repositório criado no GitHub
- [ ] Projeto enviado com `git push`
- [ ] `.gitignore` configurado
- [ ] `config.example.php` criado
- [ ] README.md completo
- [ ] Secrets FTP configurados no GitHub
- [ ] Arquivo deploy.yml na pasta `.github/workflows/`
- [ ] Banco de dados criado em produção
- [ ] `config.php` criado manualmente em produção
- [ ] Permissões ajustadas no servidor
- [ ] Primeiro deploy testado com sucesso

---

## 🚀 Próximos Passos

1. **Executar checklist acima**
2. **Fazer primeiro push**
3. **Testar deploy automático**
4. **Acessar sistema em produção**
5. **Alterar senha padrão**
6. **Criar novos usuários**

**Pronto para produção! 🎉**
