"""Pydantic-модели запроса/ответа."""
from __future__ import annotations

from typing import Literal

from pydantic import BaseModel, Field


class GenerateRequest(BaseModel):
    prompt: str = Field(..., min_length=1, max_length=2000,
                        description="Текст запроса, например «Создай продающий "
                                    "текст объявления о продаже квартиры в Ростове».")


class GenerateResponse(BaseModel):
    text: str = Field(..., description="Готовый текст (Google «Обзор от ИИ» или DuckDuckGo).")
    source: Literal["google_ai_overview", "duckduckgo"] = Field(
        "google_ai_overview", description="Откуда получен ответ: обзор Google или резерв DuckDuckGo."
    )
    elapsed_ms: int = Field(..., description="Время обработки задачи, мс.")
    attempts: int = Field(1, description="Сколько попыток понадобилось.")
