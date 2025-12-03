#!/usr/bin/env python3

import os
import base64
import requests
import json

# Configuração
REPO_OWNER = "guidutra-china"
REPO_NAME = "Impex_project_final"
BRANCH = "main"

# Ler o token do ambiente
TOKEN = os.environ.get('TOKEN_MANUS')
if not TOKEN:
    print("❌ Erro: TOKEN_MANUS não encontrado nas variáveis de ambiente")
    print("Use: export TOKEN_MANUS='seu_token_aqui'")
    exit(1)

# Headers para autenticação
headers = {
    "Authorization": f"token {TOKEN}",
    "Accept": "application/vnd.github.v3+json",
    "Content-Type": "application/json"
}

# Workflows a fazer upload
workflows = {
    ".github/workflows/tests.yml": "tests.yml",
    ".github/workflows/code-quality.yml": "code-quality.yml",
    ".github/workflows/performance.yml": "performance.yml"
}

def get_file_sha(file_path):
    """Obter SHA do arquivo no GitHub"""
    url = f"https://api.github.com/repos/{REPO_OWNER}/{REPO_NAME}/contents/{file_path}"
    response = requests.get(url, headers=headers)
    
    if response.status_code == 200:
        return response.json().get('sha')
    return None

def upload_file(file_path, content):
    """Fazer upload de um arquivo via API do GitHub"""
    url = f"https://api.github.com/repos/{REPO_OWNER}/{REPO_NAME}/contents/{file_path}"
    
    # Ler o arquivo local
    with open(file_path, 'r') as f:
        file_content = f.read()
    
    # Codificar em base64
    encoded_content = base64.b64encode(file_content.encode()).decode()
    
    # Obter SHA se o arquivo já existe
    sha = get_file_sha(file_path)
    
    # Preparar dados
    data = {
        "message": f"ci: atualizar workflow {os.path.basename(file_path)}",
        "content": encoded_content,
        "branch": BRANCH
    }
    
    # Adicionar SHA se o arquivo existe
    if sha:
        data["sha"] = sha
    
    # Fazer requisição
    response = requests.put(url, headers=headers, json=data)
    
    if response.status_code in [200, 201]:
        print(f"✅ {file_path} - Enviado com sucesso!")
        return True
    else:
        print(f"❌ {file_path} - Erro: {response.status_code}")
        print(f"   Resposta: {response.text}")
        return False

def main():
    print("🚀 Iniciando upload de workflows...\n")
    
    success_count = 0
    for file_path, name in workflows.items():
        if os.path.exists(file_path):
            if upload_file(file_path, name):
                success_count += 1
        else:
            print(f"⚠️  {file_path} - Arquivo não encontrado")
    
    print(f"\n📊 Resultado: {success_count}/{len(workflows)} workflows enviados com sucesso!")
    
    if success_count == len(workflows):
        print("\n✅ Todos os workflows foram enviados com sucesso!")
        print("🎉 Os workflows devem começar a executar em breve!")
    else:
        print("\n⚠️  Alguns workflows falharam. Verifique os erros acima.")

if __name__ == "__main__":
    main()
