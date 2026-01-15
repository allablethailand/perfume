<?php
require_once('lib/connect.php');
global $conn;

// Start session for language handling
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Language handling
$lang = 'th';
if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    if (in_array($_GET['lang'], $supportedLangs)) {
        $_SESSION['lang'] = $_GET['lang'];
        $lang = $_GET['lang'];
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}

// Translation arrays
$translations = [
    'latest' => [
        'th' => 'ข่าวล่าสุด',
        'en' => 'Latest',
        'cn' => '最新消息',
        'jp' => '最新情報',
        'kr' => '최신 소식'
    ],
    'news_1' => [
        'th' => 'เปิดตัวคอลเลคชั่นน้ำหอม AI Companion 2024',
        'en' => 'New AI Companion Perfume Collection Launch 2024',
        'cn' => '2024 年 AI 伴侣香水系列新品发布',
        'jp' => 'AI コンパニオン フレグランス コレクション 2024 発売',
        'kr' => 'AI 컴패니언 향수 컬렉션 2024 출시'
    ],
    'news_2' => [
        'th' => 'ได้รับรางวัลนวัตกรรมดีไซน์ยอดเยี่ยมที่ Bangkok Design Week',
        'en' => 'Awarded Best Design Innovation at Bangkok Design Week',
        'cn' => '荣获曼谷设计周最佳设计创新奖',
        'jp' => 'バンコクデザインウィークで最優秀デザインイノベーション賞を受賞',
        'kr' => '방콕 디자인 위크에서 최우수 디자인 혁신상 수상'
    ],
    'news_3' => [
        'th' => 'น้ำหอมที่มี AI เป็นเพื่อนในทุกขวด - แต่ละขวดไม่ซ้ำใคร',
        'en' => 'Perfumes with AI Companion in Every Bottle - Each One Unique',
        'cn' => '每瓶香水都有 AI 伴侣 - 每瓶独一无二',
        'jp' => '全てのボトルに AI コンパニオンが - 各ボトルはユニーク',
        'kr' => '모든 병에 AI 컴패니언 포함 - 각각 고유함'
    ],
    'news_4' => [
        'th' => 'เปิดโชว์รูมใหม่ที่สุขุมวิท',
        'en' => 'Opening New Showroom in Sukhumvit',
        'cn' => '素坤逸新展厅开业',
        'jp' => 'スクンビットに新ショールームをオープン',
        'kr' => '수쿰윗에 새로운 쇼룸 오픈'
    ],
    
    // Symphony Section
    'symphony_label' => [
        'th' => 'ศิลปะแห่งกลิ่นหอม',
        'en' => 'The Art of Fragrance',
        'cn' => '香气的艺术',
        'jp' => '香りの芸術',
        'kr' => '향기의 예술'
    ],
    'symphony_title' => [
        'th' => 'The Symphony of Scent: A Tale of Four Souls',
        'en' => 'The Symphony of Scent: A Tale of Four Souls',
        'cn' => '香气交响曲：四个灵魂的故事',
        'jp' => '香りの交響曲：4つの魂の物語',
        'kr' => '향기의 교향곡: 네 개의 영혼 이야기'
    ],
    'symphony_text' => [
        'th' => 'เมื่อความหอมกลายเป็นศิลปะที่จับต้องได้ ในโลกที่ทุกคนพยายามโดดเด่น มีเพียงไม่กี่สิ่งที่สามารถบอกเล่าตัวตนของคุณได้ก่อนที่คุณจะเปิดปาก นั่นคือ กลิ่น ที่คุณเลือกสวมใส่ แต่น้ำหอมทั่วไปมักจางหายไปพร้อมกับเวลา เหลือไว้แค่ความทรงจำที่เลือนลาง... จนกระทั่งเราค้นพบสูตรแห่งความเป็นนิรันดร์',
        'en' => 'When fragrance becomes tangible art. In a world where everyone strives to stand out, few things can tell your story before you speak. That is, the scent you choose to wear. But ordinary perfumes fade with time, leaving only blurred memories... until we discovered the formula of eternity.',
        'cn' => '当香气成为可触摸的艺术。在每个人都努力脱颖而出的世界里，很少有东西能在你开口之前讲述你的故事。那就是你选择佩戴的香味。但普通香水会随着时间消逝，只留下模糊的记忆……直到我们发现了永恒的配方。',
        'jp' => '香りが触れることのできる芸術になる時。誰もが目立とうとする世界で、話す前にあなたの物語を語れるものはほとんどありません。それは、あなたが身につける香りです。しかし、普通の香水は時間とともに薄れ、ぼやけた記憶だけを残します…永遠の処方を発見するまで。',
        'kr' => '향기가 만질 수 있는 예술이 될 때. 모든 사람이 돋보이려고 노력하는 세상에서, 말하기 전에 당신의 이야기를 전할 수 있는 것은 거의 없습니다. 그것은 당신이 선택한 향기입니다. 하지만 일반 향수는 시간이 지나면서 흐릿한 기억만 남기며 사라집니다... 우리가 영원의 공식을 발견하기 전까지는.'
    ],
    
    // Science Section
    'science_title' => [
        'th' => 'The Science of Scent',
        'en' => 'The Science of Scent',
        'cn' => '香气的科学',
        'jp' => '香りの科学',
        'kr' => '향기의 과학'
    ],
    'science_intro' => [
        'th' => 'เบื้องหลังทุกขวดคือนวัตกรรมที่ท้าทายกฎของธรรมชาติ:',
        'en' => 'Behind every bottle is innovation that challenges the laws of nature:',
        'cn' => '每个瓶子背后都是挑战自然法则的创新：',
        'jp' => '各ボトルの背後には、自然の法則に挑戦する革新があります：',
        'kr' => '모든 병 뒤에는 자연의 법칙에 도전하는 혁신이 있습니다:'
    ],
    'supersolvent_name' => [
        'th' => 'Supersolvent',
        'en' => 'Supersolvent',
        'cn' => '超级溶剂',
        'jp' => 'スーパーソルベント',
        'kr' => '슈퍼솔벤트'
    ],
    'supersolvent_desc' => [
        'th' => 'สูตรลับที่ปราศจาก Glycols ทุกชนิด ด้วยเทคโนโลยี Perfume Sustain Release ที่ควบคุมการระเหยได้อย่างแม่นยำ ทำให้กลิ่นหอมปล่อยตัวอย่างค่อยเป็นค่อยไป ไม่รีบร้อน คล้ายดนตรีบทยาวที่บรรเลงอย่างมีชีวิต',
        'en' => 'A secret formula free from all Glycols. With Perfume Sustain Release technology that precisely controls evaporation, allowing fragrance to release gradually, unhurried, like a long musical piece playing with life.',
        'cn' => '不含任何甘油的秘密配方。采用香水持续释放技术，精确控制蒸发，让香气缓慢释放，不急不躁，就像一首长篇音乐作品在演奏生命。',
        'jp' => 'すべてのグリコールを含まない秘密の処方。香水持続リリース技術により蒸発を正確に制御し、香りをゆっくりと、急がずに放出します。生命を奏でる長い音楽作品のように。',
        'kr' => '모든 글리콜이 없는 비밀 공식. 향수 지속 방출 기술로 증발을 정밀하게 제어하여 향기가 서두르지 않고 점진적으로 방출됩니다. 생명을 연주하는 긴 음악 작품처럼.'
    ],
    'sugarfix_name' => [
        'th' => 'Sugar Fix',
        'en' => 'Sugar Fix',
        'cn' => '糖固定剂',
        'jp' => 'シュガーフィックス',
        'kr' => '슈가 픽스'
    ],
    'sugarfix_desc' => [
        'th' => 'สารตรึงกลิ่นปฏิวัติวงการที่สร้าง Hydrogen bond ได้ทุกชั้นของกลิ่น จาก Head note ที่สดใสแรกพบ ไปจนถึง Base note ที่ลึกซึ้งยาวนาน ขณะที่สารตรึงกลิ่นทั่วไปจับได้เพียง Head note เท่านั้น',
        'en' => 'Revolutionary fixative that creates Hydrogen bonds at every layer of fragrance. From vibrant Head notes at first encounter to deep, long-lasting Base notes, while common fixatives only capture Head notes.',
        'cn' => '革命性定香剂，在香气的每一层创建氢键。从初次接触时充满活力的前调到深沉持久的基调，而普通定香剂只能捕捉前调。',
        'jp' => '香りのすべての層で水素結合を作る革命的な固定剤。最初の出会いの鮮やかなトップノートから、深く長続きするベースノートまで、一般的な固定剤はトップノートしか捉えられません。',
        'kr' => '향기의 모든 층에서 수소 결합을 만드는 혁명적인 고정제. 첫 만남의 생생한 탑 노트부터 깊고 오래 지속되는 베이스 노트까지, 일반 고정제는 탑 노트만 포착합니다.'
    ],
    'result_label' => [
        'th' => 'ผลลัพธ์',
        'en' => 'Result',
        'cn' => '结果',
        'jp' => '結果',
        'kr' => '결과'
    ],
    'result_text' => [
        'th' => 'กลิ่นที่คงอยู่ยาวนานเหนือกาลเวลา เหมือนร่องรอยที่ฝังลึกในความทรงจำ และด้วยฝาไม้พิเศษ แม้แต่ขวดเองก็กลายเป็นแหล่งเก็บกลิ่นที่มีชีวิต คอยเตือนความทรงจำทุกครั้งที่คุณเดินผ่าน',
        'en' => 'A scent that lasts beyond time, like traces etched deep in memory. And with the special wooden cap, even the bottle itself becomes a living scent reservoir, awakening memories every time you pass by.',
        'cn' => '超越时间的香气，就像深深刻在记忆中的痕迹。通过特殊的木盖，即使瓶子本身也成为活的香气储存库，每次经过时唤醒记忆。',
        'jp' => '時を超えて続く香り、記憶に深く刻まれた痕跡のように。そして特別な木製キャップにより、ボトル自体が生きた香りの貯蔵庫となり、通るたびに記憶を呼び覚まします。',
        'kr' => '시간을 초월하여 지속되는 향기, 기억에 깊이 새겨진 흔적처럼. 그리고 특별한 나무 캡으로 병 자체가 살아있는 향기 저장소가 되어 지나갈 때마다 기억을 일깨웁니다.'
    ],
    
    // Vessel Section
    'vessel_title' => [
        'th' => 'The Vessel of Memory',
        'en' => 'The Vessel of Memory',
        'cn' => '记忆之瓶',
        'jp' => '記憶の器',
        'kr' => '기억의 그릇'
    ],
    'vessel_subtitle' => [
        'th' => 'ก่อนที่คุณจะสัมผัสกลิ่น... คุณจะได้สัมผัสงานศิลป์',
        'en' => 'Before you experience the scent... you experience the art',
        'cn' => '在你体验香气之前...你先体验艺术',
        'jp' => '香りを体験する前に...アートを体験します',
        'kr' => '향기를 경험하기 전에... 예술을 경험합니다'
    ],
    'vessel_bottle_desc' => [
        'th' => 'ทรงสูงเรียวสง่างามของขวดกลม สะท้อนถึงความเป็น Niche ที่ไม่ยอมประนีประนอม โลโก้ Trandar แกะขึ้นรูปอย่างสวยงามตามแนวทรงขวด ล้อไปกับเส้นสายที่ลื่นไหล เหมือนลายนิ้วมือที่เป็นเอกลักษณ์ของแต่ละคน',
        'en' => 'The elegant tall slender round bottle reflects uncompromising Niche character. The Trandar logo is beautifully carved along the bottle\'s contour, flowing with smooth lines, like a fingerprint unique to each individual.',
        'cn' => '优雅高挑的圆形瓶身反映了毫不妥协的小众特色。Trandar 标志沿着瓶子的轮廓精美雕刻，流畅的线条流动，就像每个人独特的指纹。',
        'jp' => 'エレガントで背の高いスリムな丸いボトルは、妥協のないニッチなキャラクターを反映しています。Trandar ロゴはボトルの輪郭に沿って美しく彫刻され、滑らかな線で流れ、各個人に固有の指紋のようです。',
        'kr' => '우아하고 키가 큰 슬림한 둥근 병은 타협하지 않는 니치 캐릭터를 반영합니다. Trandar 로고는 병의 윤곽을 따라 아름답게 새겨져 있으며, 각 개인에게 고유한 지문처럼 부드러운 선으로 흐릅니다.'
    ],
    'vessel_cap_intro' => [
        'th' => 'แต่สิ่งที่แตกต่างที่สุด... คือฝาไม้ ไม่ใช่แค่ฝาปกติ แต่เป็นตัวบันทึกความทรงจำ ด้วยคุณสมบัติพิเศษของไม้ที่ดูดซับกลิ่นจากตัวน้ำหอม ทำให้ทุกครั้งที่คุณเปิดขวด คุณไม่ได้แค่พบกลิ่นจากภายใน... แต่ได้สัมผัสประสบการณ์สองชั้น',
        'en' => 'But the most distinctive feature... is the wooden cap. Not just an ordinary cap, but a memory recorder. With wood\'s special property of absorbing scent from the perfume, every time you open the bottle, you don\'t just encounter the fragrance inside... but experience a dual sensation.',
        'cn' => '但最独特的特征...是木盖。不仅仅是一个普通的盖子，而是一个记忆记录器。凭借木材吸收香水气味的特殊性质，每次打开瓶子时，您不仅会遇到里面的香气...而且会体验到双重感觉。',
        'jp' => 'しかし、最も独特な特徴...それは木製キャップです。ただの普通のキャップではなく、記憶の記録装置です。木材が香水の香りを吸収する特別な性質により、ボトルを開けるたびに、中の香りに出会うだけでなく...二重の感覚を体験します。',
        'kr' => '하지만 가장 독특한 특징...그것은 나무 캡입니다. 단순한 일반 캡이 아니라 기억 기록기입니다. 향수에서 향기를 흡수하는 나무의 특별한 속성으로 인해 병을 열 때마다 안의 향기를 만날 뿐만 아니라...이중 감각을 경험합니다.'
    ],
    'vessel_cap_desc' => [
        'th' => 'เหมือนการได้ฟังทั้งบทเพลงใหม่และเสียงสะท้อนที่คงค้างอยู่ในห้อง',
        'en' => 'Like hearing both a new melody and the echo that lingers in the room.',
        'cn' => '就像同时听到新旋律和房间里的回声。',
        'jp' => '新しいメロディーと部屋に残る響きの両方を聞くようなものです。',
        'kr' => '새로운 멜로디와 방에 남아있는 메아리를 동시에 듣는 것과 같습니다.'
    ],
    'vessel_exp1' => [
        'th' => 'กลิ่นจากตัวน้ำหอมสด ใหม่ ชัดเจน',
        'en' => 'Fragrance from the perfume itself: fresh, new, clear',
        'cn' => '来自香水本身的香气：新鲜、崭新、清晰',
        'jp' => '香水自体からの香り：フレッシュ、新しい、クリア',
        'kr' => '향수 자체의 향기: 신선하고, 새롭고, 명확한'
    ],
    'vessel_exp2' => [
        'th' => 'กลิ่นจากฝาไม้ที่ซึมซับมาเรื่อย ๆ นุ่มนวล ลึกซึ้ง อบอวล',
        'en' => 'Scent from the wooden cap that has absorbed over time: soft, deep, warm',
        'cn' => '来自随时间吸收的木盖的香气：柔和、深沉、温暖',
        'jp' => '時間をかけて吸収された木製キャップからの香り：柔らかく、深く、暖かい',
        'kr' => '시간이 지나면서 흡수된 나무 캡의 향기: 부드럽고, 깊고, 따뜻한'
    ],
    'vessel_conclusion' => [
        'th' => 'บนโต๊ะเครื่องแป้งของคุณ Trandar ไม่ใช่แค่ขวดน้ำหอม แต่เป็นงานประติมากรรมที่หอมหวน แม้คุณจะไม่ได้เปิด... ก็ยังรู้สึกได้ถึงกลิ่นที่คลอเคลียอยู่รอบ ๆ ขวด',
        'en' => 'On your vanity, Trandar is not just a perfume bottle, but a fragrant sculpture. Even when unopened... you can still sense the scent lingering around the bottle.',
        'cn' => '在您的梳妆台上，Trandar 不仅仅是一个香水瓶，而是一件芬芳的雕塑。即使未打开...您仍然可以感受到瓶子周围萦绕的香气。',
        'jp' => 'あなたのバニティーの上で、Trandar は単なる香水瓶ではなく、香りのある彫刻です。開けなくても...ボトルの周りに漂う香りを感じることができます。',
        'kr' => '화장대 위에서 Trandar는 단순한 향수병이 아니라 향기로운 조각품입니다. 열지 않아도...병 주변에 남아있는 향기를 느낄 수 있습니다.'
    ],
    
    'featured_project' => [
        'th' => 'โปรเจคพิเศษ',
        'en' => 'Featured Project',
        'cn' => '特色项目',
        'jp' => '注目のプロジェクト',
        'kr' => '주요 프로젝트'
    ],
    'transforming_title' => [
        'th' => 'การเปลี่ยนแปลงประสบการณ์น้ำหอมด้วย AI',
        'en' => 'Transforming Fragrance Experience Through AI',
        'cn' => '通过 AI 改变香水体验',
        'jp' => 'AI による香りの体験の変革',
        'kr' => 'AI를 통한 향수 경험의 변화'
    ],
    'transforming_desc' => [
        'th' => 'ค้นพบว่าเราได้ปฏิวัติการออกแบบน้ำหอมด้วยเทคโนโลยี AI ที่ทันสมัย โปรเจคล่าสุดของเราแสดงให้เห็นการผสมผสานที่สมบูรณ์แบบระหว่างกลิ่นหอมและ AI ที่เป็นเพื่อนคู่ใจในทุกขวด สร้างประสบการณ์ที่ไม่เหมือนใคร',
        'en' => 'Discover how we\'ve revolutionized perfume design with modern AI technology. Our latest project showcases the perfect blend of fragrance and AI companionship in every bottle, creating unique experiences where scent meets intelligence.',
        'cn' => '探索我们如何通过现代 AI 技术革新香水设计。我们的最新项目展示了香气与 AI 伴侣的完美融合，在每个瓶中创造独特的体验。',
        'jp' => '最新の AI テクノロジーで香水デザインを革新した方法をご覧ください。最新プロジェクトでは、すべてのボトルに香りと AI コンパニオンの完璧な融合を実現し、独自の体験を生み出しています。',
        'kr' => '현대 AI 기술로 향수 디자인을 혁신한 방법을 알아보세요. 최신 프로젝트는 모든 병에 향기와 AI 컴패니언의 완벽한 조화를 보여주며 독특한 경험을 만들어냅니다.'
    ],
    'view_project' => [
        'th' => 'ดูโปรเจค',
        'en' => 'View Project',
        'cn' => '查看项目',
        'jp' => 'プロジェクトを見る',
        'kr' => '프로젝트 보기'
    ],
    'insights' => [
        'th' => 'บทความ',
        'en' => 'Insights',
        'cn' => '见解',
        'jp' => 'インサイト',
        'kr' => '인사이트'
    ],
    'latest_stories' => [
        'th' => 'เรื่องราวล่าสุด',
        'en' => 'Latest Stories',
        'cn' => '最新故事',
        'jp' => '最新ストーリー',
        'kr' => '최신 스토리'
    ],
    'cat_design' => [
        'th' => 'ดีไซน์',
        'en' => 'Design',
        'cn' => '设计',
        'jp' => 'デザイン',
        'kr' => '디자인'
    ],
    'story_1_title' => [
        'th' => 'อนาคตของการออกแบบน้ำหอมด้วย AI',
        'en' => 'The Future of AI-Powered Perfume Design',
        'cn' => 'AI 驱动香水设计的未来',
        'jp' => 'AI を活用した香水デザインの未来',
        'kr' => 'AI 기반 향수 디자인의 미래'
    ],
    'story_1_desc' => [
        'th' => 'สำรวจแนวทางนวัตกรรมในการผสมผสานเทคโนโลยี AI เข้ากับศิลปะการสร้างน้ำหอม และการมี AI เป็นเพื่อนที่เข้าใจคุณในทุกขวด',
        'en' => 'Exploring innovative approaches to blending AI technology with the art of perfume creation and having an AI companion that understands you in every bottle.',
        'cn' => '探索将 AI 技术与香水创作艺术相结合的创新方法，以及在每个瓶中拥有一个了解您的 AI 伴侣。',
        'jp' => 'AI テクノロジーと香水創作の芸術を融合させる革新的なアプローチと、各ボトルにあなたを理解する AI コンパニオンを持つことを探求します。',
        'kr' => 'AI 기술과 향수 창작 예술을 결합하는 혁신적인 접근 방식과 모든 병에서 당신을 이해하는 AI 컴패니언을 갖는 것을 탐구합니다.'
    ],
    'cat_innovation' => [
        'th' => 'นวัตกรรม',
        'en' => 'Innovation',
        'cn' => '创新',
        'jp' => 'イノベーション',
        'kr' => '혁신'
    ],
    'story_2_title' => [
        'th' => 'AI Companion - เพื่อนที่เข้าใจคุณในทุกขวด',
        'en' => 'AI Companion - A Friend Who Understands You in Every Bottle',
        'cn' => 'AI 伴侣 - 每瓶中都能理解你的朋友',
        'jp' => 'AI コンパニオン - すべてのボトルにあなたを理解する友達',
        'kr' => 'AI 컴패니언 - 모든 병에서 당신을 이해하는 친구'
    ],
    'story_2_desc' => [
        'th' => 'แต่ละขวดมีเอกลักษณ์เฉพาะตัว AI ที่ถูกสร้างขึ้นมาเพื่อเข้าใจและปรับตัวตามบุคลิกของคุณ ไม่มีสองขวดที่เหมือนกัน',
        'en' => 'Each bottle has a unique AI personality created to understand and adapt to your character. No two bottles are the same.',
        'cn' => '每个瓶子都有独特的 AI 个性，旨在理解并适应您的性格。没有两个瓶子是相同的。',
        'jp' => '各ボトルには、あなたのキャラクターを理解し適応するために作成された独自の AI パーソナリティがあります。同じボトルは 2 つとありません。',
        'kr' => '각 병에는 당신의 성격을 이해하고 적응하도록 만들어진 고유한 AI 개성이 있습니다. 같은 병은 두 개가 없습니다.'
    ],
    'cat_spotlight' => [
        'th' => 'ไฮไลท์',
        'en' => 'Spotlight',
        'cn' => '焦点',
        'jp' => 'スポットライト',
        'kr' => '스포트라이트'
    ],
    'story_3_title' => [
        'th' => 'น้ำหอมที่ได้รับรางวัลระดับสากล',
        'en' => 'Award-Winning Perfume Design',
        'cn' => '屡获殊荣的香水设计',
        'jp' => '受賞歴のある香水デザイン',
        'kr' => '수상 경력이 있는 향수 디자인'
    ],
    'story_3_desc' => [
        'th' => 'เบื้องหลังโปรเจคล่าสุดของเราที่คว้ารางวัล International Fragrance Award ด้วยการผสมผสาน AI และกลิ่นหอมอย่างลงตัว',
        'en' => 'Behind the scenes of our recent project that won the International Fragrance Award with perfect AI and scent integration.',
        'cn' => '我们最近的项目幕后花絮，凭借完美的 AI 和香气融合赢得了国际香水奖。',
        'jp' => '完璧な AI と香りの統合で国際フレグランス賞を受賞した最近のプロジェクトの舞台裏。',
        'kr' => '완벽한 AI와 향기 통합으로 국제 향수상을 수상한 최근 프로젝트의 비하인드 스토리.'
    ]
];

// Helper function
function tt($key, $lang) {
    global $translations;
    return $translations[$key][$lang] ?? $translations[$key]['en'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
     <?php include 'template/header.php'; ?>
    
 
    
</head>
<body>

   

    <?php include 'template/banner_slide.php'; ?>

    <!-- NEWS TICKER -->
    <section class="news-ticker-section">
        <div class="ticker-wrapper">
            <div class="ticker-label"><?= tt('latest', $lang) ?></div>
            <div class="ticker-content">
                <div class="ticker-track">
                    <a href="#" class="ticker-item"><?= tt('news_1', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_2', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_3', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_4', $lang) ?></a>
                    <!-- Duplicate for seamless loop -->
                    <a href="#" class="ticker-item"><?= tt('news_1', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_2', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_3', $lang) ?></a>
                    <a href="#" class="ticker-item"><?= tt('news_4', $lang) ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- BLOCK 1: THE SYMPHONY OF SCENT -->
    <section class="symphony-section">
        <div class="symphony-container">
            <div class="symphony-content">
                <span class="symphony-label"><?= tt('symphony_label', $lang) ?></span>
                <h2 class="symphony-title"><?= tt('symphony_title', $lang) ?></h2>
                <div class="symphony-text">
                    <?= tt('symphony_text', $lang) ?>
                </div>
            </div>
            <div class="symphony-visual">
                <div class="symphony-image-wrapper">
                    <img src="public/product_images/69606aab39ce7_1767926443.jpg" 
                         alt="Symphony of Scent" 
                         class="symphony-image"
                         loading="lazy">
                    <div class="symphony-overlay"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- BLOCK 2: THE SCIENCE OF SCENT -->
    <section class="science-section">
        <div class="science-grid">
            <div class="science-image">
                <img src="public/product_images/69645f1665b2c_1768185622.jpg" 
                     alt="Science of Scent" 
                     loading="lazy">
            </div>
            <div class="science-content">
                <h2 class="science-title"><?= tt('science_title', $lang) ?></h2>
                <p class="science-intro"><?= tt('science_intro', $lang) ?></p>
                
                <div class="science-features">
                    <div class="science-feature">
                        <h3 class="feature-name"><?= tt('supersolvent_name', $lang) ?></h3>
                        <p class="feature-desc"><?= tt('supersolvent_desc', $lang) ?></p>
                    </div>
                    
                    <div class="science-feature">
                        <h3 class="feature-name"><?= tt('sugarfix_name', $lang) ?></h3>
                        <p class="feature-desc"><?= tt('sugarfix_desc', $lang) ?></p>
                    </div>
                    
                    <div class="science-result">
                        <strong><?= tt('result_label', $lang) ?></strong> <?= tt('result_text', $lang) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- BLOCK 3: THE VESSEL OF MEMORY -->
    <section class="vessel-section">
        <div class="vessel-container">
            <div class="vessel-header">
                <h2 class="vessel-title"><?= tt('vessel_title', $lang) ?></h2>
                <p class="vessel-subtitle"><?= tt('vessel_subtitle', $lang) ?></p>
            </div>
            
            <div class="vessel-content">
                <div class="vessel-text-block">
                    <p><?= tt('vessel_bottle_desc', $lang) ?></p>
                    <p><?= tt('vessel_cap_intro', $lang) ?></p>
                    <p><?= tt('vessel_cap_desc', $lang) ?></p>
                    
                    <div class="vessel-experience">
                        <div class="experience-item">
                            <span class="experience-icon">•</span>
                            <span><?= tt('vessel_exp1', $lang) ?></span>
                        </div>
                        <div class="experience-item">
                            <span class="experience-icon">•</span>
                            <span><?= tt('vessel_exp2', $lang) ?></span>
                        </div>
                    </div>
                    
                    <p class="vessel-conclusion"><?= tt('vessel_conclusion', $lang) ?></p>
                </div>
                
                <div class="vessel-image">
                    <img src="public/product_images/696089dc34f0a_1767934428.jpg" 
                         alt="The Vessel" 
                         loading="lazy">
                </div>
            </div>
        </div>
    </section>

<style>
/* ===== BLOCK 1: SYMPHONY SECTION ===== */
.symphony-section {
    padding: 120px 5%;
    background: #0a0a0a;
    position: relative;
    overflow: hidden;
}

.symphony-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        linear-gradient(180deg, transparent 0%, rgba(255, 255, 255, 0.02) 100%);
    pointer-events: none;
}

.symphony-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 100px;
    align-items: center;
    position: relative;
    z-index: 1;
}

.symphony-label {
    display: inline-block;
    color: #c9a961;
    font-size: 11px;
    letter-spacing: 4px;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 30px;
    border-bottom: 1px solid #c9a961;
    padding-bottom: 8px;
}

.symphony-title {
    font-size: 64px;
    font-weight: 200;
    color: #ffffff;
    line-height: 1.1;
    margin-bottom: 50px;
    letter-spacing: -2px;
    font-family: 'Playfair Display', 'Georgia', serif;
}

.symphony-text {
    color: #b8b8b8;
    font-size: 17px;
    line-height: 2;
    font-weight: 300;
    letter-spacing: 0.3px;
}

.symphony-visual {
    position: relative;
}

.symphony-image-wrapper {
    position: relative;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.8);
}

.symphony-image {
    width: 100%;
    height: 650px;
    object-fit: cover;
    display: block;
    filter: contrast(1.1) brightness(0.95);
}

.symphony-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(180deg, transparent 40%, rgba(0, 0, 0, 0.5) 100%);
    mix-blend-mode: multiply;
}

/* ===== BLOCK 2: SCIENCE SECTION ===== */
.science-section {
    padding: 140px 5%;
    background: #f5f5f0;
    border-top: 1px solid #e0e0d8;
    border-bottom: 1px solid #e0e0d8;
}

.science-grid {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 48% 52%;
    gap: 80px;
    align-items: start;
}

.science-image {
    position: sticky;
    top: 120px;
}

.science-image img {
    width: 100%;
    height: 750px;
    object-fit: cover;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.2);
    filter: saturate(0.9);
}

.science-content {
    padding: 50px 0;
}

.science-title {
    font-size: 52px;
    font-weight: 300;
    color: #1a1a1a;
    margin-bottom: 35px;
    line-height: 1.15;
    letter-spacing: -1.5px;
    font-family: 'Playfair Display', 'Georgia', serif;
}

.science-intro {
    font-size: 19px;
    color: #2a2a2a;
    margin-bottom: 60px;
    line-height: 1.8;
    font-weight: 300;
    letter-spacing: 0.2px;
}

.science-features {
    display: flex;
    flex-direction: column;
    gap: 50px;
}

.science-feature {
    padding: 0;
    padding-left: 35px;
    background: transparent;
    border-left: 2px solid #c9a961;
}

.feature-name {
    font-size: 26px;
    font-weight: 400;
    color: #1a1a1a;
    margin-bottom: 18px;
    letter-spacing: -0.5px;
}

.feature-desc {
    font-size: 16px;
    color: #4a4a4a;
    line-height: 1.9;
    font-weight: 300;
}

.science-result {
    padding: 45px;
    background: #1a1a1a;
    color: #e8e8e8;
    font-size: 17px;
    line-height: 1.9;
    margin-top: 60px;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
    border-top: 3px solid #c9a961;
}

.science-result strong {
    color: #c9a961;
    font-weight: 500;
    font-size: 19px;
    letter-spacing: 0.5px;
}

/* ===== BLOCK 3: VESSEL SECTION ===== */
.vessel-section {
    padding: 140px 5%;
    background: #ffffff;
}

.vessel-container {
    max-width: 1300px;
    margin: 0 auto;
}

.vessel-header {
    text-align: center;
    margin-bottom: 100px;
    padding-bottom: 50px;
    border-bottom: 1px solid #e0e0e0;
}

.vessel-title {
    font-size: 58px;
    font-weight: 300;
    color: #0a0a0a;
    margin-bottom: 25px;
    letter-spacing: -2px;
    font-family: 'Playfair Display', 'Georgia', serif;
}

.vessel-subtitle {
    font-size: 20px;
    color: #5a5a5a;
    font-weight: 300;
    font-style: italic;
    letter-spacing: 0.5px;
}

.vessel-content {
    display: grid;
    grid-template-columns: 1.3fr 0.7fr;
    gap: 80px;
    align-items: center;
}

.vessel-text-block {
    font-size: 17px;
    color: #2a2a2a;
    line-height: 2;
    font-weight: 300;
}

.vessel-text-block p {
    margin-bottom: 30px;
}

.vessel-experience {
    margin: 50px 0;
    padding: 40px;
    background: #fafafa;
    border-left: 3px solid #c9a961;
}

.experience-item {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    font-size: 16px;
    color: #1a1a1a;
    align-items: flex-start;
}

.experience-item:last-child {
    margin-bottom: 0;
}

.experience-icon {
    color: #c9a961;
    font-size: 28px;
    line-height: 1;
    font-weight: 300;
}

.vessel-conclusion {
    font-size: 19px;
    font-weight: 400;
    color: #0a0a0a;
    font-style: italic;
    margin-top: 40px !important;
    letter-spacing: 0.3px;
}

.vessel-image {
    position: relative;
}

.vessel-image img {
    width: 100%;
    height: 700px;
    object-fit: cover;
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.18);
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 1024px) {
    .symphony-container,
    .science-grid,
    .vessel-content {
        grid-template-columns: 1fr;
        gap: 50px;
    }
    
    .symphony-title {
        font-size: 46px;
    }
    
    .science-title,
    .vessel-title {
        font-size: 42px;
    }
    
    .science-image {
        position: relative;
        top: 0;
    }
}

@media (max-width: 768px) {
    .symphony-section,
    .science-section,
    .vessel-section {
        padding: 80px 5%;
    }
    
    .symphony-title {
        font-size: 36px;
    }
    
    .science-title,
    .vessel-title {
        font-size: 32px;
    }
    
    .symphony-text,
    .science-intro {
        font-size: 16px;
    }
    
    .vessel-experience {
        padding: 30px;
    }
}
</style>

    <!-- FEATURED CONTENT -->
    <section class="featured-section">
        <div class="featured-image">
            <img src="public/product_images/69606aab39ce7_1767926443.jpg" 
                 alt="Featured Project"
                 loading="lazy"
                 width="960" 
                 height="800">
        </div>
        <div class="featured-content">
            <p class="featured-label"><?= tt('featured_project', $lang) ?></p>
            <h2 class="featured-title"><?= tt('transforming_title', $lang) ?></h2>
            <p class="featured-text">
                <?= tt('transforming_desc', $lang) ?>
            </p>
            <a href="?about" class="featured-link"><?= tt('view_project', $lang) ?></a>
        </div>
    </section>

    <!-- NEWS SECTION -->
    <section class="news-section">
        <div class="section-header">
            <p class="section-subtitle"><?= tt('insights', $lang) ?></p>
            <h2 class="section-title"><?= tt('latest_stories', $lang) ?></h2>
        </div>

        <div class="news-grid">
            <article class="news-card">
                <a href="#">
                    <div class="news-image">
                        <img src="public/product_images/69645f1665b2c_1768185622.jpg" 
                             alt="News Article"
                             loading="lazy"
                             width="400" 
                             height="533">
                    </div>
                    <p class="news-category"><?= tt('cat_design', $lang) ?></p>
                    <h3 class="news-title"><?= tt('story_1_title', $lang) ?></h3>
                    <p class="news-excerpt">
                        <?= tt('story_1_desc', $lang) ?>
                    </p>
                    <p class="news-meta">December 30, 2024</p>
                </a>
            </article>

            <article class="news-card">
                <a href="#">
                    <div class="news-image">
                        <img src="public/product_images/696089dc34f0a_1767934428.jpg" 
                             alt="News Article"
                             loading="lazy"
                             width="400" 
                             height="533">
                    </div>
                    <p class="news-category"><?= tt('cat_innovation', $lang) ?></p>
                    <h3 class="news-title"><?= tt('story_2_title', $lang) ?></h3>
                    <p class="news-excerpt">
                        <?= tt('story_2_desc', $lang) ?>
                    </p>
                    <p class="news-meta">December 28, 2024</p>
                </a>
            </article>

            <article class="news-card">
                <a href="#">
                    <div class="news-image">
                        <img src="public/product_images/69645f25dea49_1768185637.jpg" 
                             alt="News Article"
                             loading="lazy"
                             width="400" 
                             height="533">
                    </div>
                    <p class="news-category"><?= tt('cat_spotlight', $lang) ?></p>
                    <h3 class="news-title"><?= tt('story_3_title', $lang) ?></h3>
                    <p class="news-excerpt">
                        <?= tt('story_3_desc', $lang) ?>
                    </p>
                    <p class="news-meta">December 25, 2024</p>
                </a>
            </article>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include 'template/footer.php'; ?>



</body>
</html>