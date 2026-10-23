import os
import sys
from pathlib import Path
from PIL import Image

# Путь к исходным изображениям проекта
TARGET_DIR = Path(r"D:\odnoetazhniki\odnoetazh\images\projects\lesnye-polyany")

SIZES = {
    "thumb": {"max_size": (750, 750), "quality": 80, "suffix": "_thumb.webp"},
    "full": {"max_size": (1600, 1600), "quality": 84, "suffix": "_full.webp"}
}

def resize_and_convert(img_path: Path):
    with Image.open(img_path) as img:
        # Корректировка ориентации по EXIF, если присутствует
        try:
            exif = img.getexif()
            orientation = exif.get(0x0112)
            if orientation == 3:
                img = img.rotate(180, expand=True)
            elif orientation == 6:
                img = img.rotate(270, expand=True)
            elif orientation == 8:
                img = img.rotate(90, expand=True)
        except Exception:
            pass

        # Конвертация RGBA/P в RGB для чистого сохранения
        if img.mode in ("RGBA", "P"):
            img = img.convert("RGB")

        orig_size = img_path.stat().st_size
        results = []

        for key, conf in SIZES.items():
            img_copy = img.copy()
            img_copy.thumbnail(conf["max_size"], Image.Resampling.LANCZOS)
            
            output_filename = img_path.stem + conf["suffix"]
            output_path = img_path.parent / output_filename
            
            img_copy.save(
                output_path, 
                "WEBP", 
                quality=conf["quality"], 
                method=6  # Максимальное сжатие WebP
            )
            new_size = output_path.stat().st_size
            results.append((output_filename, new_size))
            
        return orig_size, results

def main():
    if not TARGET_DIR.exists():
        print(f"Ошибка: Директория {TARGET_DIR} не найдена!")
        sys.exit(1)

    image_extensions = {".jpg", ".jpeg", ".png"}
    images = [p for p in TARGET_DIR.rglob("*") if p.suffix.lower() in image_extensions 
              and not p.stem.endswith(("_thumb", "_full"))]

    print(f"Найдено оригинальных файлов: {len(images)}")
    print("-" * 65)

    total_orig_bytes = 0
    total_thumb_bytes = 0
    total_full_bytes = 0

    for idx, img_path in enumerate(images, 1):
        rel_path = img_path.relative_to(TARGET_DIR)
        orig_bytes, res = resize_and_convert(img_path)
        
        total_orig_bytes += orig_bytes
        thumb_bytes = next(s for f, s in res if "_thumb" in f)
        full_bytes = next(s for f, s in res if "_full" in f)
        
        total_thumb_bytes += thumb_bytes
        total_full_bytes += full_bytes

        print(f"[{idx:02d}/{len(images):02d}] {rel_path}")
        print(f"      JPG: {orig_bytes // 1024:>4} КБ  -->  Thumb: {thumb_bytes // 1024:>3} КБ | Full: {full_bytes // 1024:>3} КБ")

    print("-" * 65)
    print(f"ИТОГ ПОСЛЕ ОПТИМИЗАЦИИ:")
    print(f"  Оригинальные JPG   : {total_orig_bytes / 1024 / 1024:.2f} МБ")
    print(f"  Превью (thumb WebP): {total_thumb_bytes / 1024 / 1024:.2f} МБ (для табов)")
    print(f"  Full-HD WebP       : {total_full_bytes / 1024 / 1024:.2f} МБ (для зума/lightbox)")
    savings = (1 - (total_thumb_bytes / total_orig_bytes)) * 100
    print(f"  Экономия веса сетки: {savings:.1f}%")

if __name__ == "__main__":
    main()