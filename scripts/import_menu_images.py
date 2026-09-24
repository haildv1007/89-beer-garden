from pathlib import Path
from PIL import Image, ImageOps


SOURCE = Path(__file__).resolve().parents[1] / "storage" / "app" / "menu-import"
TARGET = Path(__file__).resolve().parents[1] / "public" / "images" / "menu"

# Product slug => supplied source image. Ambiguous combined dishes deliberately
# use one representative preparation; unrelated images are left unimported.
IMAGES = {
    "89-chim-cau-bam": "chim câu băm.png",
    "89-chim-cau-xao": "chim câu xào.png",
    "89-ca-trach-chien-la-lot": "trạch chiên lá lốt.png",
    "89-ca-bong-chien-gion": "cá bông chiên.png",
    "89-luon-xao-sa-ot": "lươn xào xả ớt (2).png",
    "89-khau-duoi-chien-gion": "kháu đuổi chiên.png",
    "89-khau-duoi-xao-dua": "khấu đuôi xào dưa.png",
    "89-thit-trau-xao-toi": "trâu xào tỏi.png",
    "89-thit-trau-xao-la-lot": "trâu xào lá lốt.png",
    "89-thit-trau-xao-mang-truc": "trâu xào măng trúc.png",
    "89-thit-trau-xao-rau-muong": "trâu xào rau muống.png",
    "89-thit-trau-xong-hoi": "trâu xông hơi.png",
    "89-nem-chua": "nem chua.png",
    "89-nem-bui": "nem bùi.png",
    "89-nem-ngua": "nem ngựa.png",
    "89-trung-cut-lon": "cút lộn.png",
    "89-trung-cut-luoc": "cút trắng.png",
    "89-lac-luoc-hoac-rang": "lạc luộc.png",
    "89-ca-chi-vang-nuong": "cá chỉ vàng.png",
    "89-muc-nuong": "mực nướng.png",
    "89-ngo-chien": "ngô chiên.png",
    "89-khoai-le-pho": "khoai lệ phố.png",
    "89-dau-phu-luot-van": "đậu chiên.png",
    "89-dau-phu-tam-hanh": "đậu tẩm hành.png",
    "89-long-xao-dua": "lòng xào dưa.png",
    "89-thit-be-xao-sa-ot": "bê xào xả ớt.png",
    "89-thit-be-xao-lan": "bê cào lăn.png",
    "89-thit-be-xong-hoi": "bê xông hơi.png",
    "89-chan-gio-hun-khoi": "chân giò hun khói.png",
    "89-gan-chay-toi": "gan cháy tỏi.png",
    "89-ba-chi-rang": "thịt rang ba chỉ.png",
    "89-trang-lon": "tràng lợn.png",
    "89-da-day-lon": "dạ dày luộc.png",
    "89-tim-lon-xao-thap-cam": "tim xào thập cẩm.png",
    "89-vit-luoc": "vịt luộc.png",
    "89-vit-rang-muoi": "vịt rang muối.png",
    "89-chan-vit-rut-xuong": "chân vịt rút xương xào xả ớt.png",
    "89-nom-chan-vit": "chân vịt rút xương nộm.png",
    "89-ga-luoc-rang-muoi-xao-gung-hoac-chung-mam": "gà rang muối.png",
    "89-thit-cho-xao-hoac-hap": "chó hấp.png",
    "89-doi-cho": "dồi chó.png",
    "89-tom-chien-hoac-hap": "tôm hấp.png",
    "89-muc-xao-hoac-hap": "mực xào.png",
    "89-rau-muong-xao": "rau muống xào.png",
    "89-mung-toi-hoac-rau-cai": "rau mồng tơi xào.png",
    "89-ngong-cai-luoc-cham-trung": "ngồng cải luộc chấm trứng.png",
    "89-rau-bi-xao": "rau bí xào.png",
    "89-com-rang-dua-bo": "cơm rang dưa bò.png",
    "89-com-rang-thap-cam": "cơm rang thập cẩm.png",
    "89-com-trang": "cơm trắng.png",
    "89-com-rang-trung": "cơm rang trứng.png",
    "89-com-rang-dui-ga": "cơm rang đùi gà.png",
    "89-com-rang-vit-quay": "cơm rang vịt quay.png",
    "89-canh-chua-thit": "canh chua thịt.png",
    "89-trung-ran": "trứng rán.png",
    "89-nom-tai-heo-thap-cam-hoac-hoa-chuoi": "nộm tai heo.png",
    "89-ech-rang-muoi": "ếch rang muối.png",
    "89-ech-xao-sa-ot-hoac-mang-cay": "ếch xào măng cay.png",
    "89-lau-thap-cam": "lẩu thập cẩm.png",
    "89-lau-ga": "lẩu gà.png",
    "89-lau-vit": "lẩu vịt.png",
    "89-lau-trau": "lẩu trâu.png",
    "89-lau-duoi-bo": "lẩu đuôi bò.png",
    "89-lau-chim-cau": "lẩu chim câu.png",
    "89-lau-ca-chep-om-dua": "cá chép om dưa.png",
    "89-lau-ech-mang-cay": "lẩu ếch măng cay.png",
    "89-bun-bo": "bún bò.png",
    "89-bun-vit": "bún vịt quay.png",
    "89-bun-ga": "bún gà.png",
    "89-bun-ca": "bún cá.png",
}


def main() -> None:
    TARGET.mkdir(parents=True, exist_ok=True)
    missing = []
    original_bytes = 0
    webp_bytes = 0

    for slug, filename in IMAGES.items():
        source = SOURCE / filename
        if not source.is_file():
            missing.append(filename)
            continue

        destination = TARGET / f"{slug}.webp"
        original_bytes += source.stat().st_size
        with Image.open(source) as image:
            image = ImageOps.exif_transpose(image).convert("RGB")
            image.thumbnail((1600, 1600), Image.Resampling.LANCZOS)
            image.save(destination, "WEBP", quality=82, method=6)
        webp_bytes += destination.stat().st_size

    print(f"converted={len(IMAGES) - len(missing)}")
    print(f"original_mb={original_bytes / 1024 / 1024:.2f}")
    print(f"webp_mb={webp_bytes / 1024 / 1024:.2f}")
    if missing:
        print("missing=" + ", ".join(missing))
        raise SystemExit(1)


if __name__ == "__main__":
    main()
