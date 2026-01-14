<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>perfume International CO., LTD.</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: 'Sarabun', sans-serif;
        background: linear-gradient(135deg, #fff5f0 0%, #ffe8db 100%);
        min-height: 100vh;
        padding: 20px;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .header {
        text-align: center;
        margin-bottom: 50px;
        animation: slideDown 0.6s ease-out;
    }
    .header h1 {
        font-size: 2.5em;
        color: #ff9900;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
    }
    .header p {
        font-size: 1.1em;
        color: #666;
        margin-bottom: 30px;
    }
    .locations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    .location-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(217, 122, 58, 0.15);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        cursor: pointer;
    }
    .location-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(217, 122, 58, 0.25);
    }
    .card-image {
        width: 100%;
        height: 220px;
        background: linear-gradient(135deg, #ff9b6d 0%, #ff7f40 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    .card-image img {
        width: 100%;
        height: 100%;
        transition: transform 0.4s ease;
    }
    .location-card:hover .card-image img {
        transform: scale(1.1);
    }
    .card-image.no-image {
        font-size: 3em;
        background: linear-gradient(135deg, #ff9b6d 0%, #ff7f40 100%);
    }
    .card-content {
        padding: 25px;
    }
    .card-content h3 {
        font-size: 1.5em;
        color: #ff9900;
        margin-bottom: 12px;
    }
    .card-content p {
        color: #666;
        font-size: 0.95em;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    .location-info {
        display: flex;
        align-items: center;
        color: #ff9900;
        margin-bottom: 15px;
        font-weight: 500;
    }
    .location-info::before {
        content: "📍";
        margin-right: 8px;
        font-size: 1.2em;
    }
    .card-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    .btn {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.95em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-view {
        background: linear-gradient(135deg, #ff9b6d 0%, #ff7f40 100%);
        color: white;
    }
    .btn-view:hover {
        box-shadow: 0 5px 15px rgba(217, 122, 58, 0.4);
        transform: translateY(-2px);
    }
    .btn-map {
        background: #f0f0f0;
        color: #ff9900;
        border: 2px solid #ff9900;
    }
    .btn-map:hover {
        background: #ff9900;
        color: white;
        transform: translateY(-2px);
    }
    .section-title {
        text-align: center;
        font-size: 1.8em;
        color: #ff9900;
        margin: 50px 0 30px;
        position: relative;
        padding-bottom: 15px;
    }
    .section-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: linear-gradient(135deg, #ff9b6d 0%, #ff7f40 100%);
        border-radius: 2px;
    }
    .gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 50px;
    }
    .gallery-item {
        aspect-ratio: 1;
        border-radius: 12px;
        overflow: hidden;
        background: linear-gradient(135deg, #ff9b6d 0%, #ff7f40 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3em;
        cursor: pointer;
        transition: transform 0.3s ease;
        box-shadow: 0 5px 15px rgba(217, 122, 58, 0.15);
    }
    .gallery-item:hover {
        transform: scale(1.05);
    }
    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .footer {
        text-align: center;
        padding: 30px;
        color: #666;
        margin-top: 50px;
    }
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    @media (max-width: 768px) {
        .header h1 {
            font-size: 1.8em;
        }
        .locations-grid {
            grid-template-columns: 1fr;
        }
        .card-buttons {
            flex-direction: column;
        }
        .btn {
            width: 100%;
        }
        .gallery {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }
    }
    @media (max-width: 480px) {
        .header h1 {
            font-size: 1.5em;
        }
        .header p {
            font-size: 0.95em;
        }
        .card-content h3 {
            font-size: 1.2em;
        }
        .section-title {
            font-size: 1.3em;
        }
    }
</style>
</head>
<body>
    <div class="container" style="margin: 50px auto;">
        <div class="locations-grid">
            <div class="location-card">
                <div class="card-image no-image">
                    <img src="perfume International.svg" alt="">
                </div>
                <div class="card-content">
                    <h3>แทรนดาร์ อินเตอร์เนชั่นแนล พัฒนาการ 40</h3>
                    <div class="location-info">
                        perfume International Phatthanakan 40
                    </div>
                    <div class="card-buttons">
                        <a href="https://maps.google.com/?q=perfume International Phatthanakan 40" target="_blank" class="btn btn-map">Google Maps</a>
                    </div>
                </div>
            </div>
            <div class="location-card">
                <div class="card-image no-image">
                    <img src="perfume Store.svg" alt="">
                </div>
                <div class="card-content">
                    <h3>แทรนดาร์ สโตร์ ดิสทริบิวชั่น เซ็นเตอร์ </h3>
                    <div class="location-info">
                        perfume Store Distribution Center
                    </div>
                    <div class="card-buttons">
                        <a href="https://maps.google.com/?q=perfume Store Distribution Center" target="_blank" class="btn btn-map">Google Maps</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2025 perfume International CO., LTD. All Rights Reserved</p>
        </div>
    </div>
</body>
</html>