"""Живой тест извлечения обзора от ИИ (без HTTP-слоя). Запуск:
   .venv/Scripts/python.exe -m ai_service._livetest [headless]
"""
import asyncio
import sys

from ai_service.config import settings
from ai_service.google_ai import AIOverviewNotFound, GoogleAIClient


async def main() -> None:
    settings.headless = "--headed" not in sys.argv
    settings.debug_dumps = True
    prompt = "Создай продающий текст объявления о продаже квартиры в Ростове"
    client = GoogleAIClient(settings)
    await client.start()
    try:
        text = await client.fetch_overview(prompt)
        import io
        with io.open("ai_service/debug/overview.txt", "w", encoding="utf-8") as f:
            f.write(text)
        print("=== OK, длина:", len(text), "-> ai_service/debug/overview.txt")
    except AIOverviewNotFound as exc:
        print("НЕ НАЙДЕН:", exc)
    finally:
        await client.stop()


if __name__ == "__main__":
    asyncio.run(main())
