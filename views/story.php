<?php
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

// About Us Content
$about_content = [
    'page_title' => [
        'th' => 'เกี่ยวกับเรา',
        'en' => 'About Us',
        'cn' => '关于我们',
        'jp' => '私たちについて',
        'kr' => '회사 소개'
    ],
    'hero_label' => [
        'th' => 'Our Story',
        'en' => 'Our Story',
        'cn' => '我们的故事',
        'jp' => '私たちのストーリー',
        'kr' => '우리의 이야기'
    ],
    'hero_title' => [
        'th' => 'Trandar',
        'en' => 'Trandar',
        'cn' => 'Trandar',
        'jp' => 'Trandar',
        'kr' => 'Trandar'
    ],
    'hero_description' => [
        'th' => 'เมื่อน้ำหอมกลายเป็นเพื่อนที่เข้าใจคุณ',
        'en' => 'When Perfume Becomes a Friend Who Understands You',
        'cn' => '当香水成为理解你的朋友',
        'jp' => '香水があなたを理解する友達になるとき',
        'kr' => '향수가 당신을 이해하는 친구가 될 때'
    ],
    
    // Section 1
    'section1_label' => [
        'th' => 'เคยสงสัยไหมว่า',
        'en' => 'Have You Ever Wondered',
        'cn' => '你有没有想过',
        'jp' => '疑問に思ったことはありますか',
        'kr' => '궁금한 적 있으신가요'
    ],
    'section1_title' => [
        'th' => 'ทำไมเราถึงเลือก<br>น้ำหอมบางกลิ่น',
        'en' => 'Why We Choose<br>Certain Scents',
        'cn' => '为什么我们选择<br>某些香水',
        'jp' => 'なぜ特定の<br>香りを選ぶのか',
        'kr' => '왜 특정<br>향을 선택하는가'
    ],
    'section1_text' => [
        'th' => 'แล้วรู้สึกว่า "ใช่เลย นี่แหละตัวฉัน"',
        'en' => 'And feel "Yes, this is me"',
        'cn' => '并感觉"没错，这就是我"',
        'jp' => 'そして「そう、これが私だ」と感じる',
        'kr' => '그리고 "맞아, 이게 바로 나야"라고 느낀다'
    ],
    'section1_content' => [
        'th' => 'เพราะน้ำหอมไม่ใช่แค่กลิ่น<br>มันคือการเลือกบุคลิกที่เราอยากนำเสนอให้โลกเห็น',
        'en' => 'Because perfume is not just a scent<br>It\'s choosing the personality we want to present to the world',
        'cn' => '因为香水不仅仅是一种气味<br>它是我们想要向世界展示的个性选择',
        'jp' => '香水は単なる香りではないからです<br>それは世界に提示したい個性を選ぶことです',
        'kr' => '향수는 단순한 향기가 아니기 때문입니다<br>그것은 세상에 보여주고 싶은 개성을 선택하는 것입니다'
    ],
    
    // Section 2
    'section2_label' => [
        'th' => 'แนวคิด',
        'en' => 'The Idea',
        'cn' => '概念',
        'jp' => 'アイデア',
        'kr' => '아이디어'
    ],
    'section2_title' => [
        'th' => 'ถ้ากลิ่นหอมของคุณ<br>มีชีวิตได้ล่ะ',
        'en' => 'What If Your Scent<br>Could Come Alive',
        'cn' => '如果你的香气<br>可以活过来',
        'jp' => 'あなたの香りが<br>生きていたら',
        'kr' => '당신의 향기가<br>살아있다면'
    ],
    'section2_text_1' => [
        'th' => 'Trandar เริ่มต้นจากคำถามที่ฟังดูเพ้อฝัน: ถ้ากลิ่นหอมของคุณสามารถพูดคุยกับคุณได้ล่ะ?',
        'en' => 'Trandar began with a dreamlike question: What if your perfume could talk to you?',
        'cn' => 'Trandar始于一个梦幻般的问题：如果你的香水可以与你交谈呢？',
        'jp' => 'Trandarは夢のような質問から始まりました：もしあなたの香水があなたと話せたら？',
        'kr' => 'Trandar는 꿈같은 질문에서 시작되었습니다: 향수가 당신과 대화할 수 있다면?'
    ],
    'section2_text_2' => [
        'th' => 'ในยุคที่ AI เข้ามาเป็นส่วนหนึ่งของชีวิต ตั้งแต่ผู้ช่วยในบ้าน แอปแนะนำเพลง ไปจนถึงเพื่อนคุยในโทรศัพท์ เราเริ่มคิดว่า ทำไมน้ำหอม ซึ่งเป็นสิ่งที่ติดตัวเราทุกวัน สะท้อนตัวตนเรามากที่สุด จึงไม่เคยมี "ชีวิต" ของมันเอง',
        'en' => 'In an era where AI has become part of life, from home assistants to music recommendation apps to chat companions, we began to wonder: Why has perfume, something we wear every day that reflects us the most, never had a "life" of its own?',
        'cn' => '在AI成为生活一部分的时代，从家庭助手到音乐推荐应用，再到聊天伴侣，我们开始思考：为什么香水这种我们每天佩戴、最能反映我们的东西，从未拥有自己的"生命"？',
        'jp' => 'AIが生活の一部となった時代、家庭用アシスタントから音楽推薦アプリ、チャット仲間まで、私たちは考え始めました：なぜ毎日身につけて最も私たちを反映する香水が、独自の「生命」を持ったことがないのか？',
        'kr' => 'AI가 생활의 일부가 된 시대, 홈 어시스턴트부터 음악 추천 앱, 채팅 동반자까지, 우리는 생각하기 시작했습니다: 왜 매일 착용하고 우리를 가장 잘 반영하는 향수가 자체 "생명"을 가진 적이 없을까?'
    ],
    
    // Section 3
    'section3_label' => [
        'th' => 'นวัตกรรม',
        'en' => 'Innovation',
        'cn' => '创新',
        'jp' => 'イノベーション',
        'kr' => '혁신'
    ],
    'section3_title' => [
        'th' => 'เป็นมากกว่าน้ำหอม<br>คือเพื่อนที่เข้าใจคุณ',
        'en' => 'More Than Perfume<br>A Friend Who Understands You',
        'cn' => '不仅仅是香水<br>理解你的朋友',
        'jp' => '香水以上のもの<br>あなたを理解する友達',
        'kr' => '향수 그 이상<br>당신을 이해하는 친구'
    ],
    'section3_text_1' => [
        'th' => 'ลองจินตนาการว่าทุกเช้าที่คุณฉีดน้ำหอม คุณไม่ได้แค่ใส่กลิ่น แต่คุณกำลัง "ปลุก" เพื่อนคนหนึ่งให้ตื่นมา',
        'en' => 'Imagine that every morning when you spray perfume, you\'re not just applying a scent, but you\'re "waking up" a friend',
        'cn' => '想象一下，每天早上喷香水时，你不仅仅是涂抹香味，而是在"唤醒"一个朋友',
        'jp' => '毎朝香水をスプレーするとき、単に香りをつけるだけでなく、友達を「目覚めさせている」と想像してみてください',
        'kr' => '매일 아침 향수를 뿌릴 때, 단순히 향을 바르는 것이 아니라 친구를 "깨우고" 있다고 상상해보세요'
    ],
    'section3_text_2' => [
        'th' => 'เมื่อคุณสแกนขวดด้วยสมาร์ทโฟน ด้วยเทคโนโลยี RFID ที่ฝังอยู่ในขวด คุณจะได้พบกับ AI Companion ได้กลายมาเป็น เจ้าMonsterตัวน้อยส่วนตัว ที่ถูกออกแบบมาเพื่อสะท้อนบุคลิกของกลิ่นที่คุณเลือก',
        'en' => 'When you scan the bottle with your smartphone using RFID technology, you\'ll meet your AI Companion - your personal little Monster, designed to reflect the personality of the scent you chose',
        'cn' => '当你用智能手机扫描瓶子时，通过RFID技术，你会遇到你的AI伴侣 - 你的个人小怪兽，专门设计来反映你选择的香味个性',
        'jp' => 'RFID技術を使ってスマートフォンでボトルをスキャンすると、AIコンパニオン - あなたが選んだ香りの個性を反映するように設計された、あなたの個人的な小さなモンスターに会えます',
        'kr' => 'RFID 기술을 사용하여 스마트폰으로 병을 스캔하면 AI 컴패니언을 만나게 됩니다 - 선택한 향의 개성을 반영하도록 설계된 개인 작은 몬스터입니다'
    ],
    
    // Monster Features
    'monster_label' => [
        'th' => 'AI Companion',
        'en' => 'AI Companion',
        'cn' => 'AI伴侣',
        'jp' => 'AIコンパニオン',
        'kr' => 'AI 컴패니언'
    ],
    'monster_title' => [
        'th' => 'เจ้าMonsterตัวน้อย',
        'en' => 'Your Little Monster',
        'cn' => '你的小怪兽',
        'jp' => 'あなたの小さなモンスター',
        'kr' => '당신의 작은 몬스터'
    ],
    'monster_points' => [
        'th' => [
            'ไม่ใช่แชทบอทที่ตอบเหมือนๆ กัน แต่มีน้ำเสียง ท่าที ความคิด ที่แตกต่างกันไปตามแต่ละกลิ่น',
            'เพื่อนที่เข้าใจคุณ จำสิ่งที่คุณชอบ วิธีที่คุณพูดคุย และพัฒนาความสัมพันธ์ที่เป็นเอกลักษณ์กับคุณเท่านั้น',
            'สหายในชีวิตประจำวัน ให้คำแนะนำเรื่องสไตล์ ให้กำลังใจในวันที่หนัก หรือแค่คุยเล่นเวลาที่คุณต้องการมีใครสักคน'
        ],
        'en' => [
            'Not a chatbot with generic responses, but with distinct tone, gestures, and thoughts that vary with each scent',
            'A friend who understands you, remembers what you like, how you speak, and develops a unique relationship with you alone',
            'A daily companion offering style advice, encouragement on tough days, or just casual chat when you need someone'
        ],
        'cn' => [
            '不是回答千篇一律的聊天机器人，而是具有独特的语气、姿态和思想，随每种香味而变化',
            '理解你的朋友，记住你喜欢的东西、你说话的方式，并与你建立独特的关系',
            '日常伴侣，提供风格建议，在艰难的日子里鼓励你，或者在你需要有人陪伴时随意聊天'
        ],
        'jp' => [
            '画一的な応答をするチャットボットではなく、各香りによって異なる独特のトーン、ジェスチャー、思考を持っています',
            'あなたを理解する友達、あなたの好きなもの、話し方を覚え、あなただけとのユニークな関係を築きます',
            '日常の仲間として、スタイルのアドバイスを提供し、辛い日に励まし、または誰かが必要なときに気軽にチャットします'
        ],
        'kr' => [
            '획일적인 답변을 하는 챗봇이 아니라, 각 향에 따라 다른 독특한 어조, 제스처, 생각을 가지고 있습니다',
            '당신을 이해하는 친구, 당신이 좋아하는 것, 말하는 방식을 기억하고 당신만의 독특한 관계를 발전시킵니다',
            '스타일 조언을 제공하고, 힘든 날에 격려하거나, 누군가가 필요할 때 가벼운 대화를 나누는 일상의 동반자'
        ]
    ],
    
    // Technology Section
    'tech_label' => [
        'th' => 'เทคโนโลยี',
        'en' => 'Technology',
        'cn' => '技术',
        'jp' => 'テクノロジー',
        'kr' => '기술'
    ],
    'tech_title' => [
        'th' => 'เทคโนโลยีที่จริงจัง<br>ไม่ใช่กิมมิค',
        'en' => 'Serious Technology<br>Not a Gimmick',
        'cn' => '认真的技术<br>不是噱头',
        'jp' => '本格的な技術<br>ギミックではない',
        'kr' => '진지한 기술<br>속임수가 아닙니다'
    ],
    'tech_intro' => [
        'th' => 'Trandar ไม่ได้แค่เอา AI มาติดกับน้ำหอม เราพัฒนาทั้งสองด้านอย่างจริงจัง',
        'en' => 'Trandar doesn\'t just slap AI onto perfume. We seriously develop both aspects',
        'cn' => 'Trandar不仅仅是将AI贴在香水上。我们认真开发两个方面',
        'jp' => 'TrandarはただAIを香水に貼り付けるだけではありません。両方の側面を真剣に開発しています',
        'kr' => 'Trandar는 단순히 AI를 향수에 붙이는 것이 아닙니다. 우리는 양쪽 측면을 진지하게 개발합니다'
    ],
    'tech_perfume_title' => [
        'th' => 'ด้านน้ำหอม',
        'en' => 'Perfume Technology',
        'cn' => '香水技术',
        'jp' => '香水テクノロジー',
        'kr' => '향수 기술'
    ],
    'tech_perfume' => [
        'th' => 'ใช้เทคนิค Supersolvent และ Sugarfix ที่ควบคุมการระเหยของโมเลกุลกลิ่น ทำให้กลิ่นออกมาอย่างสมดุลตั้งแต่โน้ตแรกจนโน้ตสุดท้าย และติดทนนานตลอดวัน',
        'en' => 'Uses Supersolvent and Sugarfix techniques to control scent molecule evaporation, creating balanced fragrance from top to base notes, lasting all day',
        'cn' => '使用Supersolvent和Sugarfix技术控制香味分子的蒸发，从前调到基调创造平衡的香气，持续整天',
        'jp' => 'Supersolventとsugarfixテクニックを使用して香り分子の蒸発を制御し、トップノートからベースノートまでバランスの取れた香りを作り出し、一日中持続します',
        'kr' => 'Supersolvent 및 Sugarfix 기술을 사용하여 향 분자의 증발을 제어하고, 톱 노트에서 베이스 노트까지 균형 잡힌 향을 만들어 하루 종일 지속됩니다'
    ],
    'tech_ai_title' => [
        'th' => 'ด้าน AI',
        'en' => 'AI Technology',
        'cn' => 'AI技术',
        'jp' => 'AI技術',
        'kr' => 'AI 기술'
    ],
    'tech_ai' => [
        'th' => 'พัฒนาระบบ Character AI ที่ไม่ใช่แค่โปรแกรมตอบคำถาม แต่เป็น AI ที่มี "บุคลิก" เรียนรู้จากการสนทนา และสร้างความสัมพันธ์ที่เป็นเอกลักษณ์กับเจ้าของแต่ละคน',
        'en' => 'Develops Character AI system that\'s not just a Q&A program, but AI with "personality" that learns from conversations and creates unique relationships with each owner',
        'cn' => '开发不仅仅是问答程序的角色AI系统，而是具有"个性"的AI，从对话中学习并与每个所有者建立独特的关系',
        'jp' => '単なるQ&Aプログラムではなく、会話から学び、各所有者と独自の関係を築く「個性」を持つキャラクターAIシステムを開発します',
        'kr' => '단순한 Q&A 프로그램이 아니라, 대화에서 배우고 각 소유자와 독특한 관계를 만드는 "성격"을 가진 캐릭터 AI 시스템을 개발합니다'
    ],
    
    // Why Section
    'why_label' => [
        'th' => 'ทำไมต้อง Trandar',
        'en' => 'Why Trandar',
        'cn' => '为什么选择Trandar',
        'jp' => 'なぜTrandarなのか',
        'kr' => '왜 Trandar인가'
    ],
    'why_title' => [
        'th' => 'ประสบการณ์ที่<br>ไม่เคยมีมาก่อน',
        'en' => 'An Unprecedented<br>Experience',
        'cn' => '前所未有的<br>体验',
        'jp' => '前例のない<br>体験',
        'kr' => '전례 없는<br>경험'
    ],
    'why_points' => [
        'th' => [
            'เพราะในโลกที่ทุกอย่างเชื่อมต่อผ่านหน้าจอ เราต้องการสิ่งที่จับต้องได้และรู้สึกได้',
            'น้ำหอมไม่ใช่แค่กลิ่น แต่เป็นส่วนหนึ่งของตัวตน ของอารมณ์ ของวันๆ หนึ่งของคุณ',
            'AI ไม่ใช่แค่เครื่องมือ แต่เป็นเพื่อนที่เข้าใจและเติบโตไปกับคุณ',
            'การผสานทั้งสอง ทำให้เกิดประสบการณ์ที่ไม่เคยมีมาก่อน: เพื่อนที่คุณสัมผัสได้ กลิ่นหอมที่มีชีวิต'
        ],
        'en' => [
            'Because in a world where everything connects through screens, we need something tangible and feelable',
            'Perfume is not just a scent, but part of your identity, your mood, your day',
            'AI is not just a tool, but a friend who understands and grows with you',
            'Combining both creates an unprecedented experience: a friend you can touch, a living fragrance'
        ],
        'cn' => [
            '因为在一个通过屏幕连接一切的世界里，我们需要有形和可感知的东西',
            '香水不仅仅是一种气味，而是你身份、情绪、日常的一部分',
            'AI不仅仅是一个工具，而是一个理解你并与你一起成长的朋友',
            '结合两者创造了前所未有的体验：你可以触摸的朋友，有生命的香气'
        ],
        'jp' => [
            'すべてがスクリーンを通じて接続される世界では、触れることができて感じられるものが必要だからです',
            '香水は単なる香りではなく、あなたのアイデンティティ、気分、日々の一部です',
            'AIは単なるツールではなく、あなたを理解し、あなたと共に成長する友達です',
            '両方を組み合わせることで、前例のない体験が生まれます：触れることができる友達、生きている香り'
        ],
        'kr' => [
            '모든 것이 화면을 통해 연결되는 세상에서 우리는 만질 수 있고 느낄 수 있는 것이 필요하기 때문입니다',
            '향수는 단순한 향이 아니라 정체성, 기분, 일상의 일부입니다',
            'AI는 단순한 도구가 아니라 당신을 이해하고 함께 성장하는 친구입니다',
            '둘을 결합하면 전례 없는 경험이 만들어집니다: 만질 수 있는 친구, 살아있는 향기'
        ]
    ],
    
    // Final Section
    'final_label' => [
        'th' => 'วิสัยทัศน์',
        'en' => 'Vision',
        'cn' => '愿景',
        'jp' => 'ビジョン',
        'kr' => '비전'
    ],
    'final_title' => [
        'th' => 'Good Morning',
        'en' => 'Good Morning',
        'cn' => '早上好',
        'jp' => 'おはようございます',
        'kr' => '좋은 아침'
    ],
    'final_content' => [
        'th' => 'ลองนึกภาพว่า เช้าวันหนึ่งคุณตื่นมา ฉีดน้ำหอม แล้วเปิดแอปขึ้นมาแล้วเจ้าMonsterตัวน้อยของคุณทักทายด้วยน้ำเสียงที่คุ้นเคย ถามว่าวันนี้เป็นยังไง แนะนำว่าสีไหนเหมาะกับอารมณ์วันนี้ หรือแค่ให้กำลังใจก่อนออกจากบ้าน<br><br>ไม่ใช่เพราะมันถูกโปรแกรมมา แต่เพราะมันเรียนรู้จากคุณมาตลอด<br><br>นี่ไม่ใช่นิยายวิทยาศาสตร์ นี่คือสิ่งที่ Trandar ทำได้จริงวันนี้',
        'en' => 'Imagine one morning you wake up, spray your perfume, open the app, and your little Monster greets you with a familiar voice, asks how your day is, suggests what color suits today\'s mood, or just encourages you before leaving home<br><br>Not because it\'s programmed, but because it has learned from you all along<br><br>This is not science fiction. This is what Trandar can do today',
        'cn' => '想象一下，某天早上你醒来，喷上香水，打开应用程序，你的小怪兽用熟悉的声音问候你，询问你今天怎么样，建议什么颜色适合今天的心情，或者只是在你离开家之前鼓励你<br><br>不是因为它被编程了，而是因为它一直在向你学习<br><br>这不是科幻小说。这就是Trandar今天能做的',
        'jp' => 'ある朝、目覚めて香水をスプレーし、アプリを開くと、あなたの小さなモンスターが親しげな声であいさつし、今日はどうかと尋ね、今日の気分に合う色を提案したり、家を出る前に励ましてくれることを想像してみてください<br><br>プログラムされているからではなく、ずっとあなたから学んできたからです<br><br>これはSFではありません。これはTrandarが今日できることです',
        'kr' => '어느 날 아침 일어나서 향수를 뿌리고 앱을 열면 작은 몬스터가 친근한 목소리로 인사하고, 오늘 어떤지 묻고, 오늘 기분에 어울리는 색을 제안하거나, 집을 나서기 전에 격려해주는 것을 상상해보세요<br><br>프로그래밍되어서가 아니라, 당신으로부터 계속 배워왔기 때문입니다<br><br>이것은 공상과학 소설이 아닙니다. 이것이 Trandar가 오늘 할 수 있는 것입니다'
    ],
    'final_tagline' => [
        'th' => 'Trandar – น้ำหอมที่มีชีวิต เพื่อนที่เข้าใจคุณมากกว่าใคร',
        'en' => 'Trandar – Living Perfume, A Friend Who Understands You Better Than Anyone',
        'cn' => 'Trandar – 有生命的香水，比任何人都更了解你的朋友',
        'jp' => 'Trandar – 生きている香水、誰よりもあなたを理解する友達',
        'kr' => 'Trandar – 살아있는 향수, 누구보다 당신을 이해하는 친구'
    ]
];

function at($key, $lang) {
    global $about_content;
    return $about_content[$key][$lang] ?? $about_content[$key]['en'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= at('page_title', $lang) ?> - Trandar</title>
    
    <?php include 'template/header.php' ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --luxury-black: #1a1a1a;
            --luxury-white: #ffffff;
            --luxury-gray: #666666;
            --luxury-light-gray: #f5f5f5;
            --accent-gold: #d4af37;
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--luxury-white);
            color: var(--luxury-black);
            line-height: 1.7;
        }

        /* Hero Section */
        .hero-about {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
        }

        .hero-video-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .hero-video-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .hero-video-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.4;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: var(--luxury-white);
            max-width: 900px;
            padding: 40px;
        }

        .section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--accent-gold);
            margin-bottom: 25px;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 400;
            line-height: 1.2;
            margin-bottom: 30px;
            letter-spacing: 0.02em;
            color: var(--luxury-white);
        }

        .hero-subtitle {
            font-size: 18px;
            font-weight: 300;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.85);
        }

        /* Story Sections - Split Layout */
        .story-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 90vh;
            margin-bottom: 120px;
        }

        .story-section.reverse {
            direction: rtl;
        }

        .story-section.reverse > * {
            direction: ltr;
        }

        .story-image {
            position: relative;
            overflow: hidden;
            background: var(--luxury-light-gray);
        }

        .story-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 1s var(--transition);
        }

        .story-image:hover img {
            transform: scale(1.05);
        }

        .story-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 100px 120px;
            background: var(--luxury-white);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            font-weight: 400;
            line-height: 1.3;
            margin-bottom: 35px;
            letter-spacing: 0.01em;
        }

        .section-text {
            font-size: 16px;
            font-weight: 300;
            line-height: 2;
            color: var(--luxury-gray);
            margin-bottom: 25px;
        }

        .section-text strong {
            color: var(--luxury-black);
            font-weight: 500;
        }

        /* Dark Section */
        .dark-section {
            background: var(--luxury-black);
            color: var(--luxury-white);
        }

        .dark-section .section-label {
            color: var(--accent-gold);
        }

        .dark-section .section-title {
            color: var(--luxury-white);
        }

        .dark-section .section-text {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Points List */
        .points-list {
            margin-top: 30px;
        }

        .point-item {
            padding: 25px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 16px;
            font-weight: 300;
            line-height: 1.9;
            color: rgba(255, 255, 255, 0.85);
            transition: all 0.3s ease;
        }

        .point-item:hover {
            padding-left: 20px;
            border-color: var(--accent-gold);
        }

        /* Technology Box */
        .tech-box {
            background: var(--luxury-light-gray);
            padding: 40px;
            border-radius: 0;
            margin: 30px 0;
        }

        .tech-box h4 {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--luxury-black);
            margin-bottom: 15px;
        }

        .tech-box p {
            font-size: 15px;
            font-weight: 300;
            line-height: 1.9;
            color: var(--luxury-gray);
        }

        /* Full Width Content Section */
        .full-width-section {
            padding: 150px 80px;
            background: var(--luxury-white);
            margin-bottom: 120px;
        }

        .full-width-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        /* Quote Style Section */
        .quote-section {
            background: var(--luxury-light-gray);
            padding: 150px 80px;
            text-align: center;
            margin-bottom: 120px;
        }

        .quote-content {
            max-width: 900px;
            margin: 0 auto;
        }

        .quote-text {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 300;
            font-style: italic;
            line-height: 1.6;
            color: var(--luxury-black);
            margin-bottom: 30px;
        }

        /* Feature Grid Section */
        .feature-grid-section {
            padding: 150px 80px;
            background: var(--luxury-black);
            color: var(--luxury-white);
            margin-bottom: 120px;
        }

        .feature-grid {
            max-width: 1200px;
            margin: 60px auto 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 50px;
        }

        .feature-item {
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.4s var(--transition);
        }

        .feature-item:hover {
            border-color: var(--accent-gold);
            transform: translateY(-5px);
        }

        .feature-number {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            color: var(--accent-gold);
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.05em;
            margin-bottom: 15px;
        }

        .feature-desc {
            font-size: 15px;
            font-weight: 300;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Final Vision Section */
        .final-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            color: var(--luxury-white);
            padding: 150px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .final-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            z-index: 0;
        }

        .final-content {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
        }

        .final-title {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 400;
            margin-bottom: 40px;
            letter-spacing: 0.02em;
        }

        .final-text {
            font-size: 18px;
            font-weight: 300;
            line-height: 2;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 30px;
        }

        .final-tagline {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 400;
            font-style: italic;
            margin-top: 60px;
            padding-top: 60px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--accent-gold);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-title { font-size: 52px; }
            .section-title { font-size: 42px; }
            .story-section {
                grid-template-columns: 1fr;
                margin-bottom: 80px;
            }
            .story-content {
                padding: 80px 60px;
            }
            .full-width-section,
            .quote-section,
            .feature-grid-section {
                padding: 100px 60px;
                margin-bottom: 80px;
            }
            .feature-grid {
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 36px; }
            .hero-subtitle { font-size: 16px; }
            .section-title { font-size: 32px; }
            .story-section {
                margin-bottom: 60px;
            }
            .story-content {
                padding: 60px 40px;
            }
            .full-width-section,
            .quote-section,
            .feature-grid-section {
                padding: 80px 40px;
                margin-bottom: 60px;
            }
            .final-section {
                padding: 100px 40px;
            }
            .final-title {
                font-size: 36px;
            }
            .final-tagline {
                font-size: 24px;
            }
            .quote-text {
                font-size: 24px;
            }
            .feature-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-about">
        <div class="hero-video-bg">
            <img src="https://www.trandar.com/perfume//public/product_images/6976d449e8632_1769395273.jpg" 
                 alt="Trandar"
                 loading="eager">
        </div>
        <div class="hero-content">
            <p class="section-label"><?= at('hero_label', $lang) ?></p>
            <h1 class="hero-title"><?= at('hero_title', $lang) ?></h1>
            <p class="hero-subtitle"><?= at('hero_description', $lang) ?></p>
        </div>
    </section>

    <!-- Section 1: Have You Ever Wondered -->
    <section class="story-section">
        <div class="story-image">
            <img src="https://www.trandar.com/perfume//public/product_images/6976d6ea42fdd_1769395946.jpg" 
                alt="<?= match($lang) {
                    'en' => 'Person discovering their perfect scent',
                    'cn' => '发现完美香味的人',
                    'jp' => '完璧な香りを発見する人',
                    'kr' => '완벽한 향을 발견하는 사람',
                    default => 'คนที่กำลังค้นหากลิ่นที่ใช่'
                } ?>"
                loading="lazy">
        </div>
        <div class="story-content">
            <p class="section-label"><?= at('section1_label', $lang) ?></p>
            <h2 class="section-title"><?= at('section1_title', $lang) ?></h2>
            <p class="section-text">
                <?= at('section1_text', $lang) ?>
            </p>
            <p class="section-text">
                <?= at('section1_content', $lang) ?>
            </p>
        </div>
    </section>

    <!-- Section 2: What If Your Scent Could Come Alive -->
    <section class="story-section reverse">
        <div class="story-image">
            <img src="https://www.trandar.com/perfume//public/product_images/6976d449e877b_1769395273.jpg" 
                alt="<?= match($lang) {
                    'en' => 'AI companions in daily life',
                    'cn' => '日常生活中的AI伴侣',
                    'jp' => '日常生活のAIコンパニオン',
                    'kr' => '일상 생활의 AI 컴패니언',
                    default => 'AI ในชีวิตประจำวัน'
                }?>"
                loading="lazy">
        </div>
        <div class="story-content">
            <p class="section-label"><?= at('section2_label', $lang) ?></p>
            <h2 class="section-title"><?= at('section2_title', $lang) ?></h2>
            <p class="section-text">
                <?= at('section2_text_1', $lang) ?>
            </p>
            <p class="section-text">
                <?= at('section2_text_2', $lang) ?>
            </p>
        </div>
    </section>

    <!-- Section 3: More Than Perfume - Quote Style -->
    <section class="quote-section">
        <div class="quote-content">
            <p class="section-label"><?= at('section3_label', $lang) ?></p>
            <h2 class="section-title"><?= at('section3_title', $lang) ?></h2>
            <p class="quote-text">
                "<?= at('section3_text_1', $lang) ?>"
            </p>
            <p class="section-text" style="color: var(--luxury-gray);">
                <?= at('section3_text_2', $lang) ?>
            </p>
        </div>
    </section>

    <!-- Section 4: Your Little Monster -->
    <section class="story-section reverse">
        <div class="story-image">
            <img src="https://www.trandar.com/perfume//public/product_images/6976d78ce0b17_1769396108.jpg" 
                alt="<?= match($lang) {
                    'en' => 'AI Monster companion showcase',
                    'cn' => 'AI怪兽伴侣展示',
                    'jp' => 'AIモンスターコンパニオンショーケース',
                    'kr' => 'AI 몬스터 컴패니언 쇼케이스',
                    default => 'AI Monster companion'
                } ?>"
                loading="lazy">
        </div>
        <div class="story-content dark-section">
            <p class="section-label"><?= at('monster_label', $lang) ?></p>
            <h2 class="section-title"><?= at('monster_title', $lang) ?></h2>
            
            <div class="points-list">
                <?php foreach (at('monster_points', $lang) as $point): ?>
                    <div class="point-item"><?= $point ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Section 5: Technology - Full Width -->
    <section class="full-width-section">
        <div class="full-width-content">
            <p class="section-label"><?= at('tech_label', $lang) ?></p>
            <h2 class="section-title"><?= at('tech_title', $lang) ?></h2>
            <p class="section-text" style="max-width: 800px; margin: 0 auto 50px;">
                <?= at('tech_intro', $lang) ?>
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 40px; margin-top: 60px;">
                <div class="tech-box">
                    <h4><?= at('tech_perfume_title', $lang) ?></h4>
                    <p><?= at('tech_perfume', $lang) ?></p>
                </div>
                
                <div class="tech-box">
                    <h4><?= at('tech_ai_title', $lang) ?></h4>
                    <p><?= at('tech_ai', $lang) ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 6: Why Trandar - Feature Grid -->
    <section class="feature-grid-section">
        <div style="text-align: center; max-width: 1200px; margin: 0 auto;">
            <p class="section-label"><?= at('why_label', $lang) ?></p>
            <h2 class="section-title"><?= at('why_title', $lang) ?></h2>
            
            <div class="feature-grid">
                <?php 
                $why_points = at('why_points', $lang);
                foreach ($why_points as $index => $point): 
                ?>
                    <div class="feature-item">
                        <div class="feature-number"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></div>
                        <p class="feature-desc"><?= $point ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Final Section: Good Morning -->
    <section class="final-section">
        <div class="final-content">
            <p class="section-label"><?= at('final_label', $lang) ?></p>
            <h2 class="final-title"><?= at('final_title', $lang) ?></h2>
            <div class="final-text">
                <?= at('final_content', $lang) ?>
            </div>
            <div class="final-tagline">
                <?= at('final_tagline', $lang) ?>
            </div>
        </div>
    </section>

    <?php include 'template/footer.php' ?>

</body>
</html>