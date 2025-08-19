# API FIPE - Arquitetura com Docker, Redis, MySQL, Cache e Autenticação

Plano implementado: ambiente Docker completo, duas APIs em PHP 8+, fila Redis, MySQL, cache, autenticação e endpoints solicitados.

## Estrutura criada

- docker-compose.yml: sobe toda a stack na rede fipe
  - mysql: MySQL 8 (porta host 3307), init.sql automático
  - redis: Redis 7 (porta host 6379)
  - api1: serviço REST (Slim 4) exposto em http://localhost:8081
  - api2-worker: worker PHP que consome fila e persiste em MySQL
- mysql/init.sql: schema SQL com tabelas brands e vehicles (chaves, índices, FK)
- API-1 (./api1):
  - Dockerfile, composer.json, .env
  - public/index.php (bootstrap Slim 4)
  - src/bootstrap.php: conexões DB/Redis, helper, middleware de autenticação
  - src/routes.php: definição dos endpoints
  - src/Services: FipeService (HTTP FIPE), CacheService (Redis), QueueService (Redis)
  - src/Repositories: BrandRepository, VehicleRepository
- API-2 (./api2):
  - Dockerfile, composer.json, .env
  - worker.php: BLPOP na fila, chama FIPE e faz upsert no MySQL
- README.md: instruções de execução e exemplos de requisição

## Endpoints implementados (API-1)

- POST /load-initial
  - Ação: busca marcas de FIPE (carros, motos, caminhões) e enfileira cada marca no Redis para a API-2
  - Auth: Bearer token (JWT HS256)
  - Resposta 200: { "enqueued": 300 }

- GET /brands
  - Ação: busca marcas armazenadas no MySQL (com cache Redis 5 min)
  - Auth: Bearer token
  - Resposta 200: lista de marcas [{id, type, code, name}]

- GET /vehicles?brand_id={id} | ?brand={nome}
  - Ação: lista códigos, modelos e observações por marca (com cache Redis 5 min)
  - Auth: Bearer token
  - Resposta 200: lista de veículos [{code, model, observations, brand_id}]

- PUT /vehicles/{code}
  - Ação: atualiza model e/ou observations do veículo; invalida cache da respectiva marca
  - Body JSON: { "model": "Novo Modelo", "observations": "Obs" }
  - Auth: Bearer token
  - Resposta 200: { "updated": true }

- GET /health
  - Ação: health check (sem auth)

## Processamento assíncrono (fila Redis)

- API-1: enfileira em fipe:brands itens com {type, code, name}
- API-2: BLPOP em fipe:brands, para cada marca:
  - upsert marca em brands
  - consulta modelos em FIPE /{tipo}/marcas/{codigo}/modelos
  - upsert veículos em vehicles

## Cache

- Redis com TTL 300s para /brands e /vehicles
- Invalida cache de vehicles da marca ao atualizar um veículo

## Autenticação

- JWT Bearer (HS256) usando segredo JWT_SECRET (padrão: devsecret)
- Todos os endpoints exigem auth, exceto /health

## Como executar

1. Subir ambiente
   - docker compose up -d --build

2. Acompanhar logs do worker
   - docker compose logs -f api2-worker

3. Token de autenticação
   - Gere um JWT HS256 com payload simples (ex.: {"sub":"admin"}) e segredo devsecret (ex.: via jwt.io).
   - Use no header: Authorization: Bearer <seu_token>

4. Fluxo de teste
   - Disparar carga inicial:
     curl -X POST http://localhost:8081/load-initial \
       -H "Authorization: Bearer <token>"
   - Ver marcas (após o worker consumir):
     curl http://localhost:8081/brands \
       -H "Authorization: Bearer <token>"
   - Ver veículos de uma marca:
     curl "http://localhost:8081/vehicles?brand_id=<id>" \
       -H "Authorization: Bearer <token>"
   - Atualizar um veículo:
     curl -X PUT http://localhost:8081/vehicles/<code> \
       -H "Authorization: Bearer <token>" \
       -H "Content-Type: application/json" \
       -d '{"model":"Novo Modelo","observations":"Obs"}'

## Observações técnicas e boas práticas aplicadas

- REST com Slim 4; camadas separadas (Services, Repositories)
- SOLID: responsabilidades bem definidas
- Clean Architecture/Code: domínio separado de infra (DB/Redis/HTTP)
- Redis como cache e fila
- MySQL com chaves únicas e FK
- JWT middleware simples
- Guzzle para HTTP FIPE; Illuminate Capsule para DB
