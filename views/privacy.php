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

// Privacy Policy Content
$privacy_content = [
    'title' => [
        'th' => 'นโยบายความเป็นส่วนตัว',
        'en' => 'Privacy Policy',
        'cn' => '隐私政策',
        'jp' => 'プライバシーポリシー',
        'kr' => '개인정보 처리방침'
    ],
    'last_updated' => [
        'th' => 'อัพเดทล่าสุด: 9 มกราคม 2026',
        'en' => 'Last Updated: January 9, 2026',
        'cn' => '最后更新：2026年1月9日',
        'jp' => '最終更新日：2026年1月9日',
        'kr' => '최종 업데이트: 2026년 1월 9일'
    ],
    'intro' => [
        'th' => 'AI Companion Perfume ("เรา", "บริษัท", หรือ "เรา") มุ่งมั่นที่จะปกป้องความเป็นส่วนตัวของคุณ นโยบายความเป็นส่วนตัวนี้อธิบายว่าเราเก็บรวบรวม ใช้ และเปิดเผยข้อมูลส่วนบุคคลของคุณอย่างไร',
        'en' => 'AI Companion Perfume ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and disclose your personal information.',
        'cn' => 'AI伴侣香水（"我们"、"我们的"或"我们"）致力于保护您的隐私。本隐私政策说明了我们如何收集、使用和披露您的个人信息。',
        'jp' => 'AIコンパニオンフレグランス（「当社」、「当社の」または「私たち」）は、お客様のプライバシーを保護することに取り組んでいます。本プライバシーポリシーでは、お客様の個人情報をどのように収集、使用、開示するかについて説明します。',
        'kr' => 'AI 컴패니언 향수("당사", "우리" 또는 "우리의")는 귀하의 개인정보를 보호하기 위해 최선을 다하고 있습니다. 본 개인정보 처리방침은 당사가 귀하의 개인정보를 수집, 사용 및 공개하는 방법을 설명합니다.'
    ],
    'section1_title' => [
        'th' => '1. ข้อมูลที่เราเก็บรวบรวม',
        'en' => '1. Information We Collect',
        'cn' => '1. 我们收集的信息',
        'jp' => '1. 当社が収集する情報',
        'kr' => '1. 당사가 수집하는 정보'
    ],
    'section1_content' => [
        'th' => 'เราอาจเก็บรวบรวมข้อมูลประเภทต่อไปนี้:<br><br>
        <strong>ข้อมูลส่วนบุคคล:</strong> ชื่อ อีเมล หมายเลขโทรศัพท์ ที่อยู่จัดส่ง และข้อมูลการชำระเงิน<br><br>
        <strong>ข้อมูล AI Companion:</strong> การตั้งค่า การโต้ตอบ และข้อมูลการใช้งานที่เกี่ยวข้องกับ AI companion ของคุณ<br><br>
        <strong>ข้อมูลการใช้งาน:</strong> ข้อมูลเกี่ยวกับการเข้าชมเว็บไซต์ของคุณ รวมถึง IP address ประเภทเบราว์เซอร์ และหน้าที่เข้าชม<br><br>
        <strong>ข้อมูล Cookies:</strong> เราใช้ cookies และเทคโนโลยีติดตามที่คล้ายกันเพื่อปรับปรุงประสบการณ์ของคุณ',
        'en' => 'We may collect the following types of information:<br><br>
        <strong>Personal Information:</strong> Name, email, phone number, shipping address, and payment information<br><br>
        <strong>AI Companion Data:</strong> Preferences, interactions, and usage data related to your AI companion<br><br>
        <strong>Usage Information:</strong> Information about your website visits, including IP address, browser type, and pages viewed<br><br>
        <strong>Cookies:</strong> We use cookies and similar tracking technologies to enhance your experience',
        'cn' => '我们可能收集以下类型的信息：<br><br>
        <strong>个人信息：</strong>姓名、电子邮件、电话号码、送货地址和付款信息<br><br>
        <strong>AI伴侣数据：</strong>与您的AI伴侣相关的偏好、互动和使用数据<br><br>
        <strong>使用信息：</strong>有关您访问网站的信息，包括IP地址、浏览器类型和查看的页面<br><br>
        <strong>Cookies：</strong>我们使用cookies和类似的跟踪技术来增强您的体验',
        'jp' => '当社は以下の種類の情報を収集する場合があります：<br><br>
        <strong>個人情報：</strong>氏名、メールアドレス、電話番号、配送先住所、支払い情報<br><br>
        <strong>AIコンパニオンデータ：</strong>お客様のAIコンパニオンに関連する設定、インタラクション、使用データ<br><br>
        <strong>使用情報：</strong>IPアドレス、ブラウザの種類、閲覧したページを含む、お客様のウェブサイト訪問に関する情報<br><br>
        <strong>Cookie：</strong>お客様の体験を向上させるために、Cookieおよび類似の追跡技術を使用します',
        'kr' => '당사는 다음 유형의 정보를 수집할 수 있습니다：<br><br>
        <strong>개인정보：</strong>이름, 이메일, 전화번호, 배송 주소 및 결제 정보<br><br>
        <strong>AI 컴패니언 데이터：</strong>AI 컴패니언과 관련된 설정, 상호작용 및 사용 데이터<br><br>
        <strong>사용 정보：</strong>IP 주소, 브라우저 유형 및 방문한 페이지를 포함한 웹사이트 방문에 대한 정보<br><br>
        <strong>쿠키：</strong>당사는 귀하의 경험을 향상시키기 위해 쿠키 및 유사한 추적 기술을 사용합니다'
    ],
    'section2_title' => [
        'th' => '2. วิธีที่เราใช้ข้อมูลของคุณ',
        'en' => '2. How We Use Your Information',
        'cn' => '2. 我们如何使用您的信息',
        'jp' => '2. 当社がお客様の情報を使用する方法',
        'kr' => '2. 당사가 귀하의 정보를 사용하는 방법'
    ],
    'section2_content' => [
        'th' => 'เราใช้ข้อมูลของคุณเพื่อ:<br><br>
        • ประมวลผลและจัดส่งคำสั่งซื้อของคุณ<br>
        • ปรับแต่งประสบการณ์ AI companion ของคุณ<br>
        • สื่อสารกับคุณเกี่ยวกับผลิตภัณฑ์และบริการของเรา<br>
        • ปรับปรุงผลิตภัณฑ์และบริการของเรา<br>
        • ปฏิบัติตามข้อกำหนดทางกฎหมาย<br>
        • ป้องกันการฉ้อโกงและปกป้องสิทธิ์ของเรา',
        'en' => 'We use your information to:<br><br>
        • Process and fulfill your orders<br>
        • Personalize your AI companion experience<br>
        • Communicate with you about our products and services<br>
        • Improve our products and services<br>
        • Comply with legal obligations<br>
        • Prevent fraud and protect our rights',
        'cn' => '我们使用您的信息用于：<br><br>
        • 处理和完成您的订单<br>
        • 个性化您的AI伴侣体验<br>
        • 与您就我们的产品和服务进行沟通<br>
        • 改进我们的产品和服务<br>
        • 遵守法律义务<br>
        • 防止欺诈并保护我们的权利',
        'jp' => 'お客様の情報を以下の目的で使用します：<br><br>
        • ご注文の処理と履行<br>
        • AIコンパニオン体験のパーソナライズ<br>
        • 製品およびサービスに関するお客様とのコミュニケーション<br>
        • 製品およびサービスの改善<br>
        • 法的義務の遵守<br>
        • 詐欺の防止と当社の権利の保護',
        'kr' => '당사는 귀하의 정보를 다음 용도로 사용합니다：<br><br>
        • 주문 처리 및 이행<br>
        • AI 컴패니언 경험 개인화<br>
        • 제품 및 서비스에 대한 커뮤니케이션<br>
        • 제품 및 서비스 개선<br>
        • 법적 의무 준수<br>
        • 사기 방지 및 당사의 권리 보호'
    ],
    'section3_title' => [
        'th' => '3. การแบ่งปันข้อมูล',
        'en' => '3. Information Sharing',
        'cn' => '3. 信息共享',
        'jp' => '3. 情報の共有',
        'kr' => '3. 정보 공유'
    ],
    'section3_content' => [
        'th' => 'เราไม่ขาย แลกเปลี่ยน หรือให้เช่าข้อมูลส่วนบุคคลของคุณกับบุคคลที่สาม เราอาจแบ่งปันข้อมูลของคุณกับ:<br><br>
        • <strong>ผู้ให้บริการ:</strong> บริษัทที่ช่วยเราในการดำเนินธุรกิจ (เช่น การประมวลผลการชำระเงิน การจัดส่ง)<br>
        • <strong>พันธมิตรทางธุรกิจ:</strong> พันธมิตรที่เราร่วมมือเพื่อนำเสนอผลิตภัณฑ์และบริการ<br>
        • <strong>หน่วยงานกฎหมาย:</strong> เมื่อจำเป็นตามกฎหมายหรือเพื่อปกป้องสิทธิ์ของเรา',
        'en' => 'We do not sell, trade, or rent your personal information to third parties. We may share your information with:<br><br>
        • <strong>Service Providers:</strong> Companies that help us operate our business (e.g., payment processing, shipping)<br>
        • <strong>Business Partners:</strong> Partners we collaborate with to offer products and services<br>
        • <strong>Legal Authorities:</strong> When required by law or to protect our rights',
        'cn' => '我们不会向第三方出售、交易或出租您的个人信息。我们可能会与以下方共享您的信息：<br><br>
        • <strong>服务提供商：</strong>帮助我们运营业务的公司（例如支付处理、运输）<br>
        • <strong>业务合作伙伴：</strong>我们合作提供产品和服务的合作伙伴<br>
        • <strong>法律机构：</strong>法律要求或为保护我们的权利时',
        'jp' => 'お客様の個人情報を第三者に販売、交換、またはレンタルすることはありません。以下の場合にお客様の情報を共有する場合があります：<br><br>
        • <strong>サービスプロバイダー：</strong>当社の事業運営を支援する企業（例：決済処理、配送）<br>
        • <strong>ビジネスパートナー：</strong>製品およびサービスを提供するために協力するパートナー<br>
        • <strong>法執行機関：</strong>法律で要求される場合、または当社の権利を保護するため',
        'kr' => '당사는 귀하의 개인정보를 제3자에게 판매, 거래 또는 임대하지 않습니다. 다음과 정보를 공유할 수 있습니다：<br><br>
        • <strong>서비스 제공업체：</strong>비즈니스 운영을 지원하는 회사（예: 결제 처리, 배송）<br>
        • <strong>비즈니스 파트너：</strong>제품 및 서비스를 제공하기 위해 협력하는 파트너<br>
        • <strong>법 집행 기관：</strong>법적으로 요구되거나 당사의 권리를 보호하기 위해'
    ],
    'section4_title' => [
        'th' => '4. ความปลอดภัยของข้อมูล',
        'en' => '4. Data Security',
        'cn' => '4. 数据安全',
        'jp' => '4. データセキュリティ',
        'kr' => '4. 데이터 보안'
    ],
    'section4_content' => [
        'th' => 'เราใช้มาตรการรักษาความปลอดภัยที่เหมาะสมเพื่อปกป้องข้อมูลของคุณจากการเข้าถึง การใช้ หรือการเปิดเผยที่ไม่ได้รับอนุญาต รวมถึง:<br><br>
        • การเข้ารหัสข้อมูล SSL/TLS<br>
        • การควบคุมการเข้าถึงที่เข้มงวด<br>
        • การตรวจสอบความปลอดภัยเป็นประจำ<br>
        • การฝึกอบรมพนักงานเกี่ยวกับความเป็นส่วนตัว',
        'en' => 'We implement appropriate security measures to protect your information from unauthorized access, use, or disclosure, including:<br><br>
        • SSL/TLS data encryption<br>
        • Strict access controls<br>
        • Regular security audits<br>
        • Employee privacy training',
        'cn' => '我们采取适当的安全措施来保护您的信息免遭未经授权的访问、使用或披露，包括：<br><br>
        • SSL/TLS数据加密<br>
        • 严格的访问控制<br>
        • 定期安全审计<br>
        • 员工隐私培训',
        'jp' => 'お客様の情報を不正アクセス、使用、または開示から保護するために、以下を含む適切なセキュリティ対策を実施しています：<br><br>
        • SSL/TLSデータ暗号化<br>
        • 厳格なアクセス制御<br>
        • 定期的なセキュリティ監査<br>
        • 従業員のプライバシートレーニング',
        'kr' => '당사는 무단 액세스, 사용 또는 공개로부터 귀하의 정보를 보호하기 위해 다음을 포함한 적절한 보안 조치를 구현합니다：<br><br>
        • SSL/TLS 데이터 암호화<br>
        • 엄격한 액세스 제어<br>
        • 정기적인 보안 감사<br>
        • 직원 개인정보 보호 교육'
    ],
    'section5_title' => [
        'th' => '5. สิทธิ์ของคุณ',
        'en' => '5. Your Rights',
        'cn' => '5. 您的权利',
        'jp' => '5. お客様の権利',
        'kr' => '5. 귀하의 권리'
    ],
    'section5_content' => [
        'th' => 'คุณมีสิทธิ์ต่อไปนี้เกี่ยวกับข้อมูลส่วนบุคคลของคุณ:<br><br>
        • เข้าถึงและรับสำเนาข้อมูลของคุณ<br>
        • แก้ไขข้อมูลที่ไม่ถูกต้อง<br>
        • ลบข้อมูลของคุณ<br>
        • คัดค้านการประมวลผลข้อมูล<br>
        • จำกัดการประมวลผลข้อมูล<br>
        • โอนย้ายข้อมูล<br>
        • ถอนความยินยอม',
        'en' => 'You have the following rights regarding your personal information:<br><br>
        • Access and receive a copy of your data<br>
        • Correct inaccurate information<br>
        • Delete your data<br>
        • Object to data processing<br>
        • Restrict data processing<br>
        • Data portability<br>
        • Withdraw consent',
        'cn' => '您对您的个人信息拥有以下权利：<br><br>
        • 访问并接收您的数据副本<br>
        • 更正不准确的信息<br>
        • 删除您的数据<br>
        • 反对数据处理<br>
        • 限制数据处理<br>
        • 数据可移植性<br>
        • 撤回同意',
        'jp' => 'お客様の個人情報に関して、以下の権利があります：<br><br>
        • データへのアクセスとコピーの受け取り<br>
        • 不正確な情報の修正<br>
        • データの削除<br>
        • データ処理への異議<br>
        • データ処理の制限<br>
        • データポータビリティ<br>
        • 同意の撤回',
        'kr' => '귀하는 개인정보에 대해 다음과 같은 권리를 가집니다：<br><br>
        • 데이터 액세스 및 사본 수신<br>
        • 부정확한 정보 수정<br>
        • 데이터 삭제<br>
        • 데이터 처리에 대한 이의 제기<br>
        • 데이터 처리 제한<br>
        • 데이터 이동성<br>
        • 동의 철회'
    ],
    'section6_title' => [
        'th' => '6. ติดต่อเรา',
        'en' => '6. Contact Us',
        'cn' => '6. 联系我们',
        'jp' => '6. お問い合わせ',
        'kr' => '6. 문의하기'
    ],
    'section6_content' => [
        'th' => 'หากคุณมีคำถามเกี่ยวกับนโยบายความเป็นส่วนตัวนี้ โปรดติดต่อเรา:<br><br>
        <strong>AI Companion Perfume</strong><br>
        อีเมล: privacy@aicompanionperfume.com<br>
        โทรศัพท์: 02-722-7007<br>
        เว็บไซต์: www.aicompanionperfume.com',
        'en' => 'If you have any questions about this Privacy Policy, please contact us:<br><br>
        <strong>AI Companion Perfume</strong><br>
        Email: privacy@aicompanionperfume.com<br>
        Phone: 02-722-7007<br>
        Website: www.aicompanionperfume.com',
        'cn' => '如果您对本隐私政策有任何疑问，请联系我们：<br><br>
        <strong>AI伴侣香水</strong><br>
        电子邮件：privacy@aicompanionperfume.com<br>
        电话：02-722-7007<br>
        网站：www.aicompanionperfume.com',
        'jp' => '本プライバシーポリシーに関するご質問は、以下までお問い合わせください：<br><br>
        <strong>AIコンパニオンフレグランス</strong><br>
        メール：privacy@aicompanionperfume.com<br>
        電話：02-722-7007<br>
        ウェブサイト：www.aicompanionperfume.com',
        'kr' => '본 개인정보 처리방침에 대한 질문이 있으시면 다음으로 문의하십시오：<br><br>
        <strong>AI 컴패니언 향수</strong><br>
        이메일：privacy@aicompanionperfume.com<br>
        전화：02-722-7007<br>
        웹사이트：www.aicompanionperfume.com'
    ]
];

function pt($key, $lang) {
    global $privacy_content;
    return $privacy_content[$key][$lang] ?? $privacy_content[$key]['en'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= pt('title', $lang) ?> - AI Companion Perfume</title>
    
    <?php include 'template/header.php' ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.7;
        }

        /* Hero Section */
        .policy-hero {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .policy-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
        }

        .policy-hero-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .policy-subtitle {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #ffa719;
            margin-bottom: 20px;
        }

        .policy-title {
            font-size: 56px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .policy-last-updated {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Content Container */
        .policy-container {
            max-width: 900px;
            margin: -40px auto 100px;
            padding: 0 40px;
        }

        .policy-content {
            background: white;
            border-radius: 20px;
            padding: 60px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
        }

        /* Introduction */
        .policy-intro {
            font-size: 18px;
            line-height: 1.8;
            color: #2d2d2d;
            padding: 30px;
            background: #f8f8f8;
            border-radius: 12px;
            margin-bottom: 50px;
            border-left: 4px solid #ffa719;
        }

        /* Sections */
        .policy-section {
            margin-bottom: 50px;
        }

        .policy-section:last-child {
            margin-bottom: 0;
        }

        .policy-section-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .policy-section-content {
            font-size: 16px;
            line-height: 1.9;
            color: #2d2d2d;
        }

        .policy-section-content strong {
            color: #1a1a1a;
            font-weight: 600;
        }

        /* Contact Box */
        .policy-contact-box {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-top: 50px;
        }

        .policy-contact-box strong {
            color: #ffa719;
        }

        /* Back Button */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: #f5f5f5;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-bottom: 40px;
        }

        .back-button:hover {
            background: #1a1a1a;
            color: white;
            transform: translateX(-5px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .policy-hero {
                padding: 100px 0 60px;
            }

            .policy-hero-content {
                padding: 0 20px;
            }

            .policy-title {
                font-size: 36px;
            }

            .policy-container {
                padding: 0 20px;
                margin: -30px auto 80px;
            }

            .policy-content {
                padding: 40px 30px;
            }

            .policy-section-title {
                font-size: 22px;
            }

            .policy-intro,
            .policy-section-content {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="policy-hero">
        <div class="policy-hero-content">
            <p class="policy-subtitle">Legal</p>
            <h1 class="policy-title"><?= pt('title', $lang) ?></h1>
            <p class="policy-last-updated"><?= pt('last_updated', $lang) ?></p>
        </div>
    </section>

    <!-- Content -->
    <div class="policy-container">
        <div class="policy-content">
            <a href="?profile&lang=<?= $lang ?>" class="back-button">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <?= match($lang) {
                    'en' => 'Back to Home',
                    'cn' => '返回首页',
                    'jp' => 'ホームに戻る',
                    'kr' => '홈으로 돌아가기',
                    default => 'กลับสู่หน้าหลัก'
                } ?>
            </a>

            <!-- Introduction -->
            <div class="policy-intro">
                <?= pt('intro', $lang) ?>
            </div>

            <!-- Section 1 -->
            <div class="policy-section">
                <h2 class="policy-section-title"><?= pt('section1_title', $lang) ?></h2>
                <div class="policy-section-content">
                    <?= pt('section1_content', $lang) ?>
                </div>
            </div>

            <!-- Section 2 -->
            <div class="policy-section">
                <h2 class="policy-section-title"><?= pt('section2_title', $lang) ?></h2>
                <div class="policy-section-content">
                    <?= pt('section2_content', $lang) ?>
                </div>
            </div>

            <!-- Section 3 -->
            <div class="policy-section">
                <h2 class="policy-section-title"><?= pt('section3_title', $lang) ?></h2>
                <div class="policy-section-content">
                    <?= pt('section3_content', $lang) ?>
                </div>
            </div>

            <!-- Section 4 -->
            <div class="policy-section">
                <h2 class="policy-section-title"><?= pt('section4_title', $lang) ?></h2>
                <div class="policy-section-content">
                    <?= pt('section4_content', $lang) ?>
                </div>
            </div>

            <!-- Section 5 -->
            <div class="policy-section">
                <h2 class="policy-section-title"><?= pt('section5_title', $lang) ?></h2>
                <div class="policy-section-content">
                    <?= pt('section5_content', $lang) ?>
                </div>
            </div>

            <!-- Section 6 - Contact -->
            <div class="policy-section">
                <h2 class="policy-section-title"><?= pt('section6_title', $lang) ?></h2>
                <div class="policy-contact-box">
                    <?= pt('section6_content', $lang) ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'template/footer.php' ?>

</body>
</html>