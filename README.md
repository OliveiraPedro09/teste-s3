# teste-s3

testando o uso do aws s3 e coisa e tal

# AWS Config

- Crie um Usuário IAM com a seguinte policy 
```
{
  "Version": "0.1", // tanto faz pode ser a data de hj
  "Statement": [
    {
      "Sid": "AllowUploadReadDeleteOnTestingBucket",
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::NOME_DO_SEU_BUCKET/*"
    },
    {
      "Sid": "AllowListTestingBucket",
      "Effect": "Allow",
      "Action": [
        "s3:ListBucket"
      ],
      "Resource": "arn:aws:s3:::NOME_DO_SEU_BUCKET"
    }
  ]
}
``` 

- Salve as credenciais do usuário para colocar no .env

## instalação

```bash
npm install
```

## variáveis de ambiente

Crie um arquivo `.env` na raiz do projeto:

```env
AWS_ACCESS_KEY_ID=sua_chave
AWS_SECRET_ACCESS_KEY=sua_chave_secreta
AWS_REGION=us-east-2 ou qualquer outra regiao
AWS_BUCKET=nome-do-bucket
PORT=3000
```

## testando

```bash
node main.js
```

## Rotas

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `/upload` | Envia um arquivo ao S3 |
| GET | `/file?key=...` | Gera URL assinada (5 min) para um arquivo |
| GET | `/teste` | Testa URL assinada com arquivo fixo (`uploads/user/teste.txt`) |

## Exemplos

**Upload:**
```bash
curl.exe -X POST http://localhost:3000/upload -F "file=@caminho/para/arquivo.txt"
```

**Gerar URL:**
```bash
curl.exe "http://localhost:3000/file?key=uploads/user/arquivo.txt"
```

## notas

A vantagem(e recomendação do AWS) de usar URL temporária é que pela URL pública qualquer um pode acessar pra sempre sem controle de quem recebe a URL.
lembrar de sempre apagar os arquivos no tmp.
sempre manter o bucket em acesso privado para evitar vazamento de dados.