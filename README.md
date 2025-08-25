# FIPE Integration - API-1, API-2, Worker, Redis, MySQL

Projeto de integração assíncrona com a API FIPE, utilizando duas APIs em PHP (API-1 e API-2) e um worker para consumir fila, persistência em MySQL e cache em Redis. Arquitetura baseada em boas práticas (camadas, SOLID/DDD simplificado, REST, cache, fila).

## Arquitetura
- API-1 (porta 8081):
  - Endpoints REST protegidos com Basic Auth
  - Carga inicial: consulta marcas na FIPE e enfileira
  - Consultas ao banco: marcas e veículos (com cache Redis)
  - Atualização de veículo: modelo/observações (invalida cache)
- API-2 (porta 8082):
  - Health check simples (opcional)
- Worker:
  - Consome a fila Redis (BRPOP), consulta modelos na FIPE por marca, grava/atualiza no MySQL
  - Executa migrações automáticas (cria tabelas) na inicialização
- Redis: cache e fila (lista)
- MySQL: persistência

Rede Docker: todos os serviços usam a rede `fipe` para resolução amigável (`mysql`, `redis`, `fipe-api1`, etc.).

## Requisitos
- Docker e Docker Compose

## Subir ambiente
1. Build das imagens

```bash
docker compose build
```

2. Instalar dependências via Composer (garantindo PHP 8.3)

Recomendado: executar o Composer dentro dos próprios containers de cada serviço (as imagens já possuem o Composer instalado e estão em PHP 8.3):

```bash
# API-1
docker compose run --rm api1 composer install --no-interaction --prefer-dist
# API-2
docker compose run --rm api2 composer install --no-interaction --prefer-dist
```
3. Subir serviços

```bash
docker compose up -d mysql redis api1 api2 worker
```

Aguarde alguns segundos para o MySQL ficar pronto.

## Configuração (variáveis importantes)
- Banco (em docker-compose.yml):
  - DB_HOST=mysql, DB_PORT=3307, DB_NAME=fipe, DB_USER=fipe, DB_PASS=fipe
- Redis: REDIS_HOST=redis, REDIS_PORT=6379, REDIS_CACHE_TTL=300
- Fila: QUEUE_BRANDS_KEY=brands_queue
- FIPE base URL (v1 por padrão): FIPE_BASE_URL=https://parallelum.com.br/fipe/api/v1/carros
- Auth (API-1): APP_USER=admin, APP_PASS=admin123

## Validação fim a fim

1. Health check API-2 (opcional)

```bash
curl http://localhost:8082
# {"status":"ok","service":"api2"}
```

2. Disparar carga inicial (enfileira marcas)

```bash
curl -X POST -u admin:admin123 http://localhost:8081/v1/seed
# {"message":"Brands enqueued","count":N}
```

3. Acompanhar o worker (migrações + consumo de fila)

```bash
docker logs -f fipe-worker
```

4. Consultar marcas

```bash
curl -u admin:admin123 http://localhost:8081/v1/marcas
```

5. Consultar veículos por marca (com cache)

```bash
curl -u admin:admin123 "http://localhost:8081/v1/veiculos?marca_codigo=59"
```

6. Atualizar veículo (modelo/observações) e invalidar cache da marca

```bash
curl -X PUT -u admin:admin123 -H "Content-Type: application/json" \
  -d '{"modelo":"Novo Modelo","observacoes":"Observação atualizada"}' \
  http://localhost:8081/v1/veiculos/ID_DO_VEICULO
```

7. Consultas diretas no MySQL (opcional)

```bash
docker exec -it fipe-mysql mysql -u fipe -pfipe fipe -e "SHOW TABLES;"
docker exec -it fipe-mysql mysql -u fipe -pfipe fipe -e "SELECT COUNT(*) brands FROM brands; SELECT COUNT(*) models FROM models;"
```

## Endpoints (API-1)
- Segurança: Basic Auth com APP_USER/APP_PASS

- POST /v1/seed
  - Ação: Enfileira marcas obtidas da FIPE
  - Resposta: { message: string, count: number }

- GET /v1/marcas
  - Ação: Lista marcas do banco
  - Resposta: [ { id, code, name } ]

- GET /v1/veiculos?marca_codigo={codigo}
  - Ação: Lista veículos por marca com cache
  - Resposta: [ { id, code, brand_code, marca, modelo, observacoes } ]

- PUT /v1/veiculos/{id}
  - Body: { modelo?: string, observacoes?: string }
  - Ação: Atualiza dados e invalida cache
  - Resposta: { updated: boolean }

## Estrutura das tabelas (MySQL)
- brands
  - id INT UNSIGNED AUTO_INCREMENT PK
  - code VARCHAR(50) UNIQUE
  - name VARCHAR(255)
- models
  - id INT UNSIGNED AUTO_INCREMENT PK
  - brand_id INT UNSIGNED FK -> brands(id) ON DELETE CASCADE
  - code VARCHAR(50)
  - name VARCHAR(255)
  - notes TEXT NULL
  - created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  - updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  - UNIQUE(brand_id, code)

## Observações de design
- Camadas: Controllers -> Services -> Infra/Repositories
- Fila com Redis (LPUSH/BRPOP)
- Cache Redis com TTL configurável e invalidação
- Migrações idempotentes no worker
- Uso de Guzzle para HTTP client

## Troubleshooting
- MySQL não pronto: aguarde mais alguns segundos; verifique logs: `docker logs -f fipe-mysql`
- Dependências PHP faltando: garanta que executou os passos de Composer em api1 e api2
- Fila sem consumo: confirme `docker logs -f fipe-worker` e que o Redis está up
- Limites da FIPE API: evite bater limite diário; se necessário, crie token ou reduza chamadas de seed

## Licença
Projeto de teste prático.
