"""FastAPI-приложение микросервиса ai_service.

Эндпоинты:
  POST /generate  — принять строку-запрос, вернуть текст (обзор от ИИ Google);
  GET  /health    — проверка живости и глубины очереди.

Запуск:  python -m ai_service.main   (или uvicorn ai_service.main:app)
"""
from __future__ import annotations

import asyncio
from contextlib import asynccontextmanager

import uvicorn
from fastapi import FastAPI, HTTPException
from loguru import logger

from .config import settings
from .models import GenerateRequest, GenerateResponse
from .queue_worker import GenerationQueue

_queue: GenerationQueue | None = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    global _queue
    _queue = GenerationQueue(settings)
    await _queue.start()
    logger.info("ai_service готов на {}:{}", settings.host, settings.port)
    try:
        yield
    finally:
        await _queue.stop()


app = FastAPI(title="aviton ai_service", version="1.0.0", lifespan=lifespan)


@app.get("/health")
async def health() -> dict:
    return {
        "status": "ok",
        "queue_depth": _queue.depth if _queue else None,
        "headless": settings.headless,
        "overview_max_retries": settings.overview_max_retries,
        "duckduckgo_fallback": settings.duckduckgo_enabled,
    }


@app.post("/generate", response_model=GenerateResponse)
async def generate(req: GenerateRequest) -> GenerateResponse:
    if _queue is None:
        raise HTTPException(status_code=503, detail="Сервис ещё не готов")
    try:
        return await _queue.submit(req.prompt)
    except asyncio.TimeoutError:
        raise HTTPException(status_code=504, detail="Превышено время ожидания задачи")
    except RuntimeError as exc:
        raise HTTPException(status_code=502, detail=str(exc))


def main() -> None:
    uvicorn.run(
        "ai_service.main:app",
        host=settings.host,
        port=settings.port,
        reload=False,
    )


if __name__ == "__main__":
    main()
