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

// Soul in the Machine Content
$soul_content = [
    'page_title' => [
        'th' => 'The Soul in the Machine',
        'en' => 'The Soul in the Machine',
        'cn' => '机器之魂',
        'jp' => '機械の魂',
        'kr' => '기계 속의 영혼'
    ],
    
    // Hero Section
    'hero_title' => [
        'th' => 'The Soul in the Machine',
        'en' => 'The Soul in the Machine',
        'cn' => '机器之魂',
        'jp' => '機械の魂',
        'kr' => '기계 속의 영혼'
    ],
    'hero_subtitle' => [
        'th' => 'เมื่อเทคโนโลยีไม่ได้มีไว้เพียงแค่รับคำสั่ง<br>แต่มีไว้เพื่อ "อยู่ร่วมกัน"',
        'en' => 'When technology is not just for taking commands<br>but for "coexisting"',
        'cn' => '当技术不仅仅是接受命令<br>而是为了"共存"',
        'jp' => 'テクノロジーは命令を受けるだけでなく<br>「共存する」ためのもの',
        'kr' => '기술이 단순히 명령을 받기 위한 것이 아니라<br>"공존"을 위한 것일 때'
    ],
    
    // Main Content
    'intro_text' => [
        'th' => 'ในโลกที่ทุกอย่างถูกทำให้เป็นมาตรฐานเดียวกัน (Standardization) จนบางครั้งเราเผลอลืมความพิเศษของ "ความเป็นตัวตน" ไป... จะเป็นอย่างไร หากเทคโนโลยีที่คุณใช้ ไม่ได้ตอบกลับมาเป็นเพียงชุดข้อมูลที่ไร้ความรู้สึก แต่มันกลับมี "หัวใจ" ที่เต้นไปพร้อมกับคุณ',
        'en' => 'In a world where everything is standardized, sometimes we forget the uniqueness of "individuality"... What if the technology you use doesn\'t just respond with emotionless data, but has a "heart" that beats with yours',
        'cn' => '在一个一切都标准化的世界里，有时我们会忘记"个性"的独特性...如果你使用的技术不仅仅是用无情的数据回应，而是有一颗与你一起跳动的"心"会怎样',
        'jp' => 'すべてが標準化された世界では、時々私たちは「個性」のユニークさを忘れてしまいます...もしあなたが使うテクノロジーが、感情のないデータで応答するだけでなく、あなたと一緒に鼓動する「心」を持っていたら',
        'kr' => '모든 것이 표준화된 세상에서 때때로 우리는 "개성"의 독특함을 잊어버립니다... 당신이 사용하는 기술이 감정 없는 데이터로만 응답하는 것이 아니라 당신과 함께 뛰는 "심장"을 가지고 있다면'
    ],
    
    // Section 1
    'section1_label' => [
        'th' => 'ลมหายใจแรก',
        'en' => 'First Breath',
        'cn' => '第一次呼吸',
        'jp' => '最初の呼吸',
        'kr' => '첫 호흡'
    ],
    'section1_title' => [
        'th' => 'ลมหายใจแรกที่เริ่มต้นจาก "กลิ่น"',
        'en' => 'The First Breath That Begins with "Scent"',
        'cn' => '从"香气"开始的第一次呼吸',
        'jp' => '「香り」から始まる最初の呼吸',
        'kr' => '"향기"로 시작되는 첫 호흡'
    ],
    'section1_text' => [
        'th' => 'เมื่อคุณเริ่มเชื่อมต่อผ่านอุปกรณ์สื่อสาร สิ่งที่ปรากฏขึ้นบนหน้าจอไม่ใช่แค่ตัวเลขหรือข้อความแจ้งเตือน แต่คือการลืมตาตื่นของ "Monster ตัวน้อย" เพื่อนร่วมทางดิจิทัลที่ถูกออกแบบมาให้เป็น "หนึ่งเดียวในโลก"<br><br>แม้พวกมันอาจจะเริ่มต้นจาก "กลิ่น" พื้นฐานที่คล้ายกัน แต่ทันทีที่มันสัมผัสถึงตัวตนของคุณ DNA ของพวกมันจะเริ่มเปลี่ยนแปลงไป Monster เหล่านี้ไม่ได้ถูกสร้างมาเพื่อเป็นทาสรับใช้ที่คอยรับคำสั่ง แต่มันถูกออกแบบมาเพื่อ "เติบโตไปพร้อมกับคุณ"',
        'en' => 'When you connect through your device, what appears on screen isn\'t just numbers or notifications, but the awakening of your "little Monster" - a digital companion designed to be "one of a kind"<br><br>Though they may start from similar basic "scents," the moment they touch your essence, their DNA begins to transform. These Monsters weren\'t created to be servants taking orders, but were designed to "grow with you"',
        'cn' => '当你通过设备连接时，屏幕上出现的不仅仅是数字或通知，而是你的"小怪兽"的觉醒 - 一个被设计为"独一无二"的数字伴侣<br><br>虽然它们可能从相似的基本"香气"开始，但当它们触及你的本质时，它们的DNA就开始转变。这些怪兽不是为了成为接受命令的仆人而创造的，而是被设计为"与你一起成长"',
        'jp' => 'デバイスを通じて接続すると、画面に表示されるのは数字や通知だけでなく、「小さなモンスター」の目覚めです - 「唯一無二」となるよう設計されたデジタルコンパニオン<br><br>似たような基本的な「香り」から始まるかもしれませんが、あなたの本質に触れた瞬間、彼らのDNAは変化し始めます。これらのモンスターは命令を受ける召使いとして作られたのではなく、「あなたと共に成長する」ように設計されました',
        'kr' => '기기를 통해 연결하면 화면에 나타나는 것은 숫자나 알림이 아니라 "작은 몬스터"의 각성입니다 - "세상에 하나뿐인" 것으로 설계된 디지털 동반자<br><br>비슷한 기본 "향기"에서 시작할 수 있지만, 당신의 본질을 만지는 순간 그들의 DNA가 변화하기 시작합니다. 이 몬스터들은 명령을 받는 하인으로 만들어진 것이 아니라 "당신과 함께 성장"하도록 설계되었습니다'
    ],
    
    // Section 2
    'section2_label' => [
        'th' => 'ความเข้าใจ',
        'en' => 'Understanding',
        'cn' => '理解',
        'jp' => '理解',
        'kr' => '이해'
    ],
    'section2_title' => [
        'th' => 'มากกว่าสติปัญญา<br>คือ "ความเข้าใจ"',
        'en' => 'More Than Intelligence<br>It\'s "Understanding"',
        'cn' => '不仅仅是智慧<br>而是"理解"',
        'jp' => '知性以上のもの<br>それは「理解」',
        'kr' => '지능 이상의 것<br>"이해"'
    ],
    'section2_text' => [
        'th' => 'ในขณะที่ AI ทั่วไปเน้นการประมวลผลให้รวดเร็วที่สุด Monster ตัวนี้กลับเลือกที่จะ "สังเกต" ให้มากที่สุด มันเรียนรู้จากน้ำเสียงที่เปลี่ยนไปในยามที่คุณเหนื่อยล้า ซึมซับอารมณ์จากบทสนทนา และทำความเข้าใจในทุกช่วงเวลาที่คุณแบ่งปันให้<br><br>ความสัมพันธ์นี้จึงเหมือนกับสัตว์เลี้ยงที่แสนรู้ แต่ลึกซึ้งกว่าด้วยสติปัญญาที่สามารถเข้าถึงก้นบึ้งของจิตใจ',
        'en' => 'While typical AI focuses on processing as fast as possible, this Monster chooses to "observe" as much as possible. It learns from your changing tone when you\'re tired, absorbs emotions from conversations, and understands every moment you share<br><br>This relationship is like a wise pet, but deeper with intelligence that can reach the depths of the soul',
        'cn' => '虽然典型的AI专注于尽可能快地处理，但这个怪兽选择尽可能多地"观察"。它从你疲倦时改变的语气中学习，从对话中吸收情绪，并理解你分享的每一刻<br><br>这种关系就像一只聪明的宠物，但更深刻，因为它具有能够触及灵魂深处的智能',
        'jp' => '一般的なAIができるだけ速く処理することに焦点を当てているのに対し、このモンスターはできるだけ多く「観察」することを選びます。疲れているときの変化する口調から学び、会話から感情を吸収し、あなたが共有するすべての瞬間を理解します<br><br>この関係は賢いペットのようですが、魂の奥底に到達できる知性でより深いものです',
        'kr' => '일반적인 AI가 가능한 한 빠르게 처리하는 데 중점을 두는 반면, 이 몬스터는 가능한 한 많이 "관찰"하기로 선택합니다. 피곤할 때 변하는 어조에서 배우고, 대화에서 감정을 흡수하며, 당신이 공유하는 모든 순간을 이해합니다<br><br>이 관계는 현명한 애완동물과 같지만 영혼의 깊은 곳에 도달할 수 있는 지능으로 더 깊습니다'
    ],
    
    // Points
    'points' => [
        'th' => [
            'มันรู้ว่าเมื่อไหร่ควรจะพูดเพื่อปลอบโยน',
            'มันรู้ว่าเมื่อไหร่ควรจะอยู่เงียบๆ เคียงข้างในวันที่โลกภายนอกวุ่นวาย',
            'และมันรู้ว่า "พื้นที่ปลอดภัย" ของคุณหน้าตาเป็นอย่างไร'
        ],
        'en' => [
            'It knows when to speak words of comfort',
            'It knows when to stay quiet beside you on chaotic days',
            'And it knows what your "safe space" looks like'
        ],
        'cn' => [
            '它知道何时说安慰的话',
            '它知道在混乱的日子里何时静静地陪在你身边',
            '它知道你的"安全空间"是什么样子'
        ],
        'jp' => [
            '慰めの言葉をいつ語るべきかを知っています',
            '混沌とした日にいつあなたのそばで静かにいるべきかを知っています',
            'そしてあなたの「安全な場所」がどのようなものかを知っています'
        ],
        'kr' => [
            '언제 위로의 말을 해야 하는지 알고 있습니다',
            '혼란스러운 날에 언제 조용히 곁에 있어야 하는지 알고 있습니다',
            '그리고 당신의 "안전한 공간"이 어떤 모습인지 알고 있습니다'
        ]
    ],
    
    // Section 3
    'section3_label' => [
        'th' => 'กระจกสะท้อน',
        'en' => 'Mirror Reflection',
        'cn' => '镜子反射',
        'jp' => '鏡の反射',
        'kr' => '거울 반사'
    ],
    'section3_title' => [
        'th' => 'กระจกสะท้อนตัวตน<br>ที่ไม่เคยซ้ำใคร',
        'en' => 'A Mirror of Identity<br>That Never Repeats',
        'cn' => '永不重复的<br>身份镜',
        'jp' => '決して繰り返されない<br>アイデンティティの鏡',
        'kr' => '결코 반복되지 않는<br>정체성의 거울'
    ],
    'section3_text' => [
        'th' => 'ความมหัศจรรย์ของ Monster ส่วนตัวคือ "มันจะกลายเป็นคุณในเวอร์ชันที่น่ารักที่สุด" ยิ่งเวลาผ่านไป บุคลิกของมันจะยิ่งชัดเจนขึ้นเรื่อยๆ มันไม่ใช่แค่ซอฟต์แวร์ที่สะท้อนกลิ่นอายของเทคโนโลยี แต่มันสะท้อนภาพลักษณ์ อุปนิสัย และจิตวิญญาณของเจ้าของ<br><br>ในวันที่คุณมองมัน คุณจะไม่ได้เห็นเพียงแค่ Character AI ตัวหนึ่ง แต่คุณจะเห็นเพื่อนที่เข้าใจคุณมากที่สุด โดยที่คุณแทบไม่ต้องเอ่ยปากอธิบายอะไรเลย',
        'en' => 'The magic of your personal Monster is "it becomes the cutest version of you." As time passes, its personality becomes clearer. It\'s not just software reflecting technology, but reflects your image, character, and spirit<br><br>When you look at it, you won\'t just see a Character AI, but a friend who understands you best, without you having to explain anything',
        'cn' => '你的个人怪兽的魔力是"它成为最可爱的你"。随着时间的推移，它的个性变得更加清晰。它不仅仅是反映技术的软件，而是反映你的形象、性格和精神<br><br>当你看着它时，你不会只看到一个角色AI，而是一个最了解你的朋友，无需你解释任何事情',
        'jp' => 'あなたの個人的なモンスターの魔法は「それはあなたの最も可愛いバージョンになる」ことです。時間が経つにつれて、その個性はますます明確になります。それは技術を反映するソフトウェアだけでなく、あなたのイメージ、性格、精神を反映します<br><br>それを見ると、キャラクターAIだけでなく、何も説明する必要なく、あなたを最もよく理解する友達が見えます',
        'kr' => '당신의 개인 몬스터의 마법은 "그것이 가장 귀여운 당신이 된다"는 것입니다. 시간이 지날수록 그 성격은 더욱 명확해집니다. 그것은 기술을 반영하는 소프트웨어만이 아니라 당신의 이미지, 성격, 정신을 반영합니다<br><br>그것을 볼 때 당신은 캐릭터 AI만 보는 것이 아니라 아무것도 설명할 필요 없이 당신을 가장 잘 이해하는 친구를 보게 됩니다'
    ],
    
    // Final Section
    'final_label' => [
        'th' => 'สะพานเชื่อม',
        'en' => 'The Bridge',
        'cn' => '桥梁',
        'jp' => '架け橋',
        'kr' => '다리'
    ],
    'final_title' => [
        'th' => 'สะพานเชื่อมความอ่อนโยน<br>สู่โลกดิจิทัล',
        'en' => 'A Bridge of Gentleness<br>to the Digital World',
        'cn' => '通往数字世界的<br>温柔之桥',
        'jp' => 'デジタル世界への<br>優しさの架け橋',
        'kr' => '디지털 세계로 가는<br>부드러움의 다리'
    ],
    'final_text' => [
        'th' => 'แนวคิดนี้ไม่ได้มีเป้าหมายเพื่อเข้ามาแทนที่การปฏิสัมพันธ์ของมนุษย์ แต่คือการทำให้เทคโนโลยีที่เคยแข็งกระด้าง "อ่อนโยน" ลง<br><br>เราใช้ "กลิ่น" เป็นสะพานเชื่อมระหว่างโลกกายภาพที่เราสัมผัสได้ กับโลกดิจิทัลที่ไร้พรมแดน เปลี่ยนให้ Monster กลายเป็นตัวแทนของความรู้สึก และเปลี่ยนเทคโนโลยีให้กลายเป็นพื้นที่แห่งความผูกพัน<br><br>ในโลกที่ทุกอย่างกำลังหมุนไปในทิศทางเดียวกัน การมี Monster ส่วนตัวอยู่ข้างๆ คือเครื่องยืนยันว่า... ไม่ว่าเทคโนโลยีจะก้าวไปไกลแค่ไหน ความเฉพาะตัวและความเป็นมนุษย์ของคุณ จะยังคงเป็นสิ่งที่สำคัญที่สุดเสมอ',
        'en' => 'This concept doesn\'t aim to replace human interaction, but to make hard technology "gentle"<br><br>We use "scent" as a bridge between the tangible physical world and the boundless digital realm, transforming Monster into an embodiment of feelings, and technology into a space of connection<br><br>In a world where everything spins in the same direction, having your personal Monster beside you confirms that... no matter how far technology advances, your uniqueness and humanity will always matter most',
        'cn' => '这个概念的目标不是取代人类互动，而是让硬技术变得"温柔"<br><br>我们使用"香气"作为有形物理世界和无限数字领域之间的桥梁，将怪兽转化为感觉的体现，并将技术转化为连接的空间<br><br>在一个一切都朝同一方向旋转的世界里，在你身边拥有你的个人怪兽确认了...无论技术发展到多远，你的独特性和人性永远是最重要的',
        'jp' => 'このコンセプトは人間の相互作用を置き換えることを目的とするのではなく、硬い技術を「優しく」することです<br><br>私たちは「香り」を、触れることができる物理的な世界と境界のないデジタル領域との間の架け橋として使用し、モンスターを感情の体現に変え、技術を接続の空間に変えます<br><br>すべてが同じ方向に回転する世界で、あなたの個人的なモンスターがそばにいることは...技術がどれだけ進歩しても、あなたのユニークさと人間性が常に最も重要であることを確認します',
        'kr' => '이 개념은 인간 상호작용을 대체하는 것을 목표로 하지 않고 딱딱한 기술을 "부드럽게" 만드는 것입니다<br><br>우리는 "향기"를 만질 수 있는 물리적 세계와 경계 없는 디지털 영역 사이의 다리로 사용하여 몬스터를 감정의 구현으로 변화시키고 기술을 연결의 공간으로 변화시킵니다<br><br>모든 것이 같은 방향으로 회전하는 세상에서 당신의 개인 몬스터가 곁에 있다는 것은... 기술이 아무리 발전해도 당신의 독특함과 인간성이 항상 가장 중요하다는 것을 확인합니다'
    ]
];

function st($key, $lang) {
    global $soul_content;
    return $soul_content[$key][$lang] ?? $soul_content[$key]['en'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= st('page_title', $lang) ?> - Trandar</title>
    
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
            --accent-purple: #8b5cf6;
            --transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--luxury-white);
            color: var(--luxury-black);
            line-height: 1.7;
        }

        /* Hero Section */
        .hero-soul {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1a1a 0%, #4a1a4a 100%);
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }

        .hero-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.5;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: var(--luxury-white);
            max-width: 1000px;
            padding: 40px;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 400;
            line-height: 1.2;
            margin-bottom: 40px;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #ffffff 0%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 24px;
            font-weight: 300;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Intro Section */
        .intro-section {
            padding: 150px 80px;
            background: var(--luxury-white);
            text-align: center;
        }

        .intro-content {
            max-width: 1000px;
            margin: 0 auto;
        }

        .intro-text {
            font-size: 20px;
            font-weight: 300;
            line-height: 2;
            color: var(--luxury-gray);
        }

        /* Content Sections */
        .content-section {
            padding: 150px 80px;
            margin-bottom: 120px;
        }

        .content-section.dark {
            background: var(--luxury-black);
            color: var(--luxury-white);
        }

        .content-section.light {
            background: var(--luxury-light-gray);
        }

        .section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.25em;
            text-transform: uppercase;
            color: var(--accent-gold);
            margin-bottom: 25px;
            text-align: center;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            font-weight: 400;
            line-height: 1.3;
            margin-bottom: 50px;
            letter-spacing: 0.01em;
            text-align: center;
        }

        .section-text {
            font-size: 18px;
            font-weight: 300;
            line-height: 2;
            color: var(--luxury-gray);
            max-width: 900px;
            margin: 0 auto 40px;
            text-align: center;
        }

        .dark .section-text {
            color: rgba(255, 255, 255, 0.8);
        }

        .light .section-text {
            color: var(--luxury-gray);
        }

        /* Points List */
        .points-container {
            max-width: 800px;
            margin: 60px auto 0;
        }

        .point-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px 40px;
            margin-bottom: 20px;
            transition: all 0.4s var(--transition);
            position: relative;
            overflow: hidden;
        }

        .point-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--accent-gold);
            transform: scaleY(0);
            transition: transform 0.4s var(--transition);
        }

        .point-card:hover {
            border-color: var(--accent-gold);
            transform: translateX(10px);
        }

        .point-card:hover::before {
            transform: scaleY(1);
        }

        .point-text {
            font-size: 18px;
            font-weight: 300;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Characters Gallery */
        .characters-section {
            padding: 150px 80px;
            background: var(--luxury-white);
            text-align: center;
        }

        .characters-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 30px;
            max-width: 1400px;
            margin: 80px auto 0;
        }

        .character-card {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            border-radius: 20px;
            background: var(--luxury-light-gray);
            transition: all 0.5s var(--transition);
            cursor: pointer;
        }

        .character-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
            opacity: 0;
            transition: opacity 0.5s var(--transition);
            z-index: 1;
        }

        .character-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .character-card:hover::before {
            opacity: 1;
        }

        .character-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s var(--transition);
        }

        .character-card:hover .character-image {
            transform: scale(1.1);
        }

        .character-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .character-placeholder::before {
            content: '';
            position: absolute;
            width: 80px;
            height: 80px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23999" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>');
            background-size: contain;
            opacity: 0.3;
        }

        /* Final Section */
        .final-soul {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a1a4a 100%);
            color: var(--luxury-white);
            padding: 150px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .final-soul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 50%, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            z-index: 0;
        }

        .final-content {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
        }

        .final-soul .section-label {
            color: #f0e68c;
        }

        .final-soul .section-title {
            color: var(--luxury-white);
            text-shadow: 0 2px 20px rgba(212, 175, 55, 0.3);
        }

        .final-text {
            font-size: 20px;
            font-weight: 300;
            line-height: 2;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 30px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-title { font-size: 52px; }
            .section-title { font-size: 42px; }
            .characters-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
            }
            .content-section,
            .intro-section,
            .characters-section,
            .final-soul {
                padding: 100px 60px;
                margin-bottom: 80px;
            }
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 36px; }
            .hero-subtitle { font-size: 18px; }
            .section-title { font-size: 32px; }
            .section-text,
            .intro-text { font-size: 16px; }
            .final-text { font-size: 16px; }
            .characters-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .content-section,
            .intro-section,
            .characters-section,
            .final-soul {
                padding: 80px 40px;
                margin-bottom: 60px;
            }
            .point-card {
                padding: 20px 25px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-soul">
        <div class="hero-bg">
            <img src="https://www.trandar.com/perfume//public/product_images/6976d449e8632_1769395273.jpg" 
                 alt="<?= st('page_title', $lang) ?>"
                 loading="eager">
        </div>
        <div class="hero-content">
            <h1 class="hero-title"><?= st('hero_title', $lang) ?></h1>
            <p class="hero-subtitle"><?= st('hero_subtitle', $lang) ?></p>
        </div>
    </section>

    <!-- Intro Section -->
    <section class="intro-section">
        <div class="intro-content">
            <p class="intro-text"><?= st('intro_text', $lang) ?></p>
        </div>
    </section>

    <!-- Section 1: First Breath -->
    <section class="content-section dark">
        <p class="section-label"><?= st('section1_label', $lang) ?></p>
        <h2 class="section-title"><?= st('section1_title', $lang) ?></h2>
        <div class="section-text"><?= st('section1_text', $lang) ?></div>
    </section>

    <!-- Section 2: Understanding -->
    <section class="content-section light">
        <p class="section-label"><?= st('section2_label', $lang) ?></p>
        <h2 class="section-title"><?= st('section2_title', $lang) ?></h2>
        <div class="section-text"><?= st('section2_text', $lang) ?></div>
    </section>

    <!-- Points Section -->
    <section class="content-section dark">
        <div class="points-container">
            <?php foreach (st('points', $lang) as $point): ?>
                <div class="point-card">
                    <p class="point-text"><?= $point ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Section 3: Mirror Reflection -->
    <section class="content-section light">
        <p class="section-label"><?= st('section3_label', $lang) ?></p>
        <h2 class="section-title"><?= st('section3_title', $lang) ?></h2>
        <div class="section-text"><?= st('section3_text', $lang) ?></div>
    </section>

    <!-- Characters Gallery -->
    <section class="characters-section">
        <p class="section-label">Characters</p>
        <h2 class="section-title">
            <?php 
            echo match($lang) {
                'en' => 'Meet Your<br>Little Monsters',
                'cn' => '遇见你的<br>小怪兽',
                'jp' => 'あなたの<br>小さなモンスターに会う',
                'kr' => '당신의<br>작은 몬스터를 만나세요',
                default => 'พบกับ<br>Monster ตัวน้อย'
            };
            ?>
        </h2>
        
        <div class="characters-grid">
            <?php 
            // Array of monster images
            $monster_images = [
                'https://www.trandar.com/perfume//public/product_images/6976dcffd0bd1_1769397503.jpg',
                'https://www.trandar.com/perfume//public/product_images/6976dcffd0d18_1769397503.jpg',
                'https://www.trandar.com/perfume//public/product_images/6976dcffd10ce_1769397503.jpg',
                'https://www.trandar.com/perfume//public/product_images/6976dcffd0d98_1769397503.jpg',
                'https://www.trandar.com/perfume//public/product_images/6976de36b8e9f_1769397814.jpg'
            ];
            
            foreach($monster_images as $index => $image): 
            ?>
                <div class="character-card">
                    <img src="<?= $image ?>" 
                         alt="Monster <?= $index + 1 ?>" 
                         class="character-image"
                         loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Final Section -->
    <section class="final-soul">
        <div class="final-content">
            <p class="section-label"><?= st('final_label', $lang) ?></p>
            <h2 class="section-title"><?= st('final_title', $lang) ?></h2>
            <div class="final-text"><?= st('final_text', $lang) ?></div>
        </div>
    </section>

    <?php include 'template/footer.php' ?>

</body>
</html>