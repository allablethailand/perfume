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

// Terms & Conditions Content
$terms_content = [
    'title' => [
        'th' => 'ข้อกำหนดและเงื่อนไข',
        'en' => 'Terms & Conditions',
        'cn' => '条款和条件',
        'jp' => '利用規約',
        'kr' => '이용약관'
    ],
    'last_updated' => [
        'th' => 'อัพเดทล่าสุด: 9 มกราคม 2026',
        'en' => 'Last Updated: January 9, 2026',
        'cn' => '最后更新：2026年1月9日',
        'jp' => '最終更新日：2026年1月9日',
        'kr' => '최종 업데이트: 2026년 1월 9일'
    ],
    'intro' => [
        'th' => 'ยินดีต้อนรับสู่ AI Companion Perfume ข้อกำหนดและเงื่อนไขเหล่านี้กำหนดกฎและข้อบังคับสำหรับการใช้เว็บไซต์และผลิตภัณฑ์ของเรา โดยการเข้าถึงเว็บไซต์นี้ เราถือว่าคุณยอมรับข้อกำหนดและเงื่อนไขเหล่านี้',
        'en' => 'Welcome to AI Companion Perfume. These Terms and Conditions outline the rules and regulations for the use of our website and products. By accessing this website, we assume you accept these terms and conditions.',
        'cn' => '欢迎来到AI伴侣香水。这些条款和条件概述了使用我们的网站和产品的规则和规定。访问本网站即表示您接受这些条款和条件。',
        'jp' => 'AIコンパニオンフレグランスへようこそ。本利用規約は、当社のウェブサイトおよび製品の使用に関する規則と規制を概説しています。本ウェブサイトにアクセスすることにより、お客様はこれらの利用規約に同意したものとみなされます。',
        'kr' => 'AI 컴패니언 향수에 오신 것을 환영합니다. 본 이용약관은 당사 웹사이트 및 제품 사용에 대한 규칙과 규정을 설명합니다. 본 웹사이트에 액세스함으로써 귀하는 본 약관에 동의하는 것으로 간주됩니다.'
    ],
    'section1_title' => [
        'th' => '1. การยอมรับข้อกำหนด',
        'en' => '1. Acceptance of Terms',
        'cn' => '1. 接受条款',
        'jp' => '1. 利用規約への同意',
        'kr' => '1. 약관 동의'
    ],
    'section1_content' => [
        'th' => 'การใช้บริการของเราถือเป็นการยอมรับข้อกำหนดเหล่านี้ ถ้าคุณไม่ยอมรับข้อกำหนดทั้งหมด โปรดอย่าใช้บริการของเรา<br><br>
        เราขอสงวนสิทธิ์ในการเปลี่ยนแปลง แก้ไข หรืออัปเดตข้อกำหนดเหล่านี้ได้ทุกเมื่อโดยไม่ต้องแจ้งให้ทราบล่วงหน้า การใช้บริการของเราอย่างต่อเนื่องหลังจากการเปลี่ยนแปลงถือเป็นการยอมรับข้อกำหนดที่แก้ไขแล้ว',
        'en' => 'By using our services, you accept these terms. If you do not accept all terms, please do not use our services.<br><br>
        We reserve the right to change, modify, or update these terms at any time without prior notice. Your continued use of our services after changes constitutes acceptance of the modified terms.',
        'cn' => '使用我们的服务即表示您接受这些条款。如果您不接受所有条款，请不要使用我们的服务。<br><br>
        我们保留随时更改、修改或更新这些条款的权利，恕不另行通知。在更改后继续使用我们的服务即表示您接受修改后的条款。',
        'jp' => '当社のサービスを使用することにより、お客様はこれらの規約に同意したものとみなされます。すべての規約に同意しない場合は、当社のサービスを使用しないでください。<br><br>
        当社は、事前の通知なしにこれらの規約をいつでも変更、修正、または更新する権利を留保します。変更後も当社のサービスを継続して使用することは、修正された規約への同意を構成します。',
        'kr' => '당사의 서비스를 사용함으로써 귀하는 본 약관에 동의하게 됩니다. 모든 약관에 동의하지 않는 경우 당사의 서비스를 사용하지 마십시오。<br><br>
        당사는 사전 통지 없이 언제든지 본 약관을 변경, 수정 또는 업데이트할 권리를 보유합니다. 변경 후 당사의 서비스를 계속 사용하는 것은 수정된 약관에 대한 동의를 구성합니다.'
    ],
    'section2_title' => [
        'th' => '2. การใช้งานและบัญชี',
        'en' => '2. Use and Account',
        'cn' => '2. 使用和账户',
        'jp' => '2. 使用とアカウント',
        'kr' => '2. 사용 및 계정'
    ],
    'section2_content' => [
        'th' => 'เพื่อเข้าถึงบางคุณสมบัติของบริการ คุณอาจต้องสร้างบัญชี คุณรับผิดชอบในการ:<br><br>
        • รักษาความปลอดภัยของบัญชีและรหัสผ่านของคุณ<br>
        • ให้ข้อมูลที่ถูกต้องและเป็นปัจจุบัน<br>
        • แจ้งให้เราทราบทันทีหากมีการใช้งานบัญชีโดยไม่ได้รับอนุญาต<br>
        • ไม่แบ่งปันข้อมูลบัญชีของคุณกับผู้อื่น<br><br>
        คุณต้องมีอายุอย่างน้อย 18 ปีหรือบรรลุนิติภาวะตามกฎหมายในเขตอำนาจศาลของคุณเพื่อใช้บริการของเรา',
        'en' => 'To access certain features of the service, you may need to create an account. You are responsible for:<br><br>
        • Maintaining the security of your account and password<br>
        • Providing accurate and current information<br>
        • Notifying us immediately of any unauthorized account use<br>
        • Not sharing your account credentials with others<br><br>
        You must be at least 18 years old or of legal age in your jurisdiction to use our services.',
        'cn' => '要访问服务的某些功能，您可能需要创建一个账户。您有责任：<br><br>
        • 维护账户和密码的安全<br>
        • 提供准确和最新的信息<br>
        • 如有任何未经授权的账户使用，立即通知我们<br>
        • 不与他人分享您的账户凭据<br><br>
        您必须年满18岁或在您所在司法管辖区达到法定年龄才能使用我们的服务。',
        'jp' => 'サービスの特定機能にアクセスするには、アカウントを作成する必要がある場合があります。お客様は以下について責任を負います：<br><br>
        • アカウントとパスワードのセキュリティの維持<br>
        • 正確かつ最新の情報の提供<br>
        • 不正なアカウント使用があった場合の即時通知<br>
        • アカウント認証情報を他人と共有しないこと<br><br>
        当社のサービスを使用するには、18歳以上またはお客様の管轄区域で法定年齢に達している必要があります。',
        'kr' => '서비스의 특정 기능에 액세스하려면 계정을 만들어야 할 수 있습니다. 귀하는 다음에 대한 책임이 있습니다：<br><br>
        • 계정 및 비밀번호의 보안 유지<br>
        • 정확하고 최신 정보 제공<br>
        • 무단 계정 사용이 있는 경우 즉시 통지<br>
        • 계정 자격 증명을 타인과 공유하지 않음<br><br>
        당사의 서비스를 사용하려면 18세 이상이거나 귀하의 관할권에서 법정 연령에 도달해야 합니다.'
    ],
    'section3_title' => [
        'th' => '3. การสั่งซื้อและการชำระเงิน',
        'en' => '3. Orders and Payment',
        'cn' => '3. 订单和付款',
        'jp' => '3. 注文と支払い',
        'kr' => '3. 주문 및 결제'
    ],
    'section3_content' => [
        'th' => 'เมื่อทำการสั่งซื้อ:<br><br>
        • คุณยืนยันว่าข้อมูลทั้งหมดที่ให้มาถูกต้องและสมบูรณ์<br>
        • ราคาและความพร้อมใช้งานอาจมีการเปลี่ยนแปลงโดยไม่ต้องแจ้งให้ทราบล่วงหน้า<br>
        • เราขอสงวนสิทธิ์ในการปฏิเสธหรือยกเลิกคำสั่งซื้อใด ๆ<br>
        • การชำระเงินต้องได้รับการประมวลผลก่อนการจัดส่ง<br>
        • ราคาทั้งหมดแสดงเป็นสกุลเงินท้องถิ่นและรวม VAT<br><br>
        <strong>AI Companion Personalization:</strong> การตั้งค่า AI companion ของคุณเป็นส่วนหนึ่งของผลิตภัณฑ์และไม่สามารถโอนหรือคืนเงินได้หลังจากการเปิดใช้งาน',
        'en' => 'When placing an order:<br><br>
        • You confirm that all information provided is accurate and complete<br>
        • Prices and availability are subject to change without notice<br>
        • We reserve the right to refuse or cancel any order<br>
        • Payment must be processed before shipping<br>
        • All prices are shown in local currency and include VAT<br><br>
        <strong>AI Companion Personalization:</strong> Your AI companion settings are part of the product and cannot be transferred or refunded after activation.',
        'cn' => '下订单时：<br><br>
        • 您确认提供的所有信息准确且完整<br>
        • 价格和可用性可能会随时更改，恕不另行通知<br>
        • 我们保留拒绝或取消任何订单的权利<br>
        • 付款必须在发货前处理<br>
        • 所有价格均以当地货币显示并包含增值税<br><br>
        <strong>AI伴侣个性化：</strong>您的AI伴侣设置是产品的一部分，激活后无法转让或退款。',
        'jp' => '注文時：<br><br>
        • 提供されたすべての情報が正確かつ完全であることを確認します<br>
        • 価格と在庫状況は予告なく変更される場合があります<br>
        • 当社は任意の注文を拒否またはキャンセルする権利を留保します<br>
        • 支払いは発送前に処理される必要があります<br>
        • すべての価格は現地通貨で表示され、VATが含まれています<br><br>
        <strong>AIコンパニオンのパーソナライゼーション：</strong>お客様のAIコンパニオン設定は製品の一部であり、有効化後は譲渡または払い戻しができません。',
        'kr' => '주문 시：<br><br>
        • 제공된 모든 정보가 정확하고 완전함을 확인합니다<br>
        • 가격과 재고 상황은 예고 없이 변경될 수 있습니다<br>
        • 당사는 모든 주문을 거부하거나 취소할 권리를 보유합니다<br>
        • 결제는 배송 전에 처리되어야 합니다<br>
        • 모든 가격은 현지 통화로 표시되며 VAT가 포함되어 있습니다<br><br>
        <strong>AI 컴패니언 개인화：</strong>귀하의 AI 컴패니언 설정은 제품의 일부이며 활성화 후 양도하거나 환불할 수 없습니다.'
    ],
    'section4_title' => [
        'th' => '4. การจัดส่งและการคืนสินค้า',
        'en' => '4. Shipping and Returns',
        'cn' => '4. 运输和退货',
        'jp' => '4. 配送と返品',
        'kr' => '4. 배송 및 반품'
    ],
    'section4_content' => [
        'th' => '<strong>การจัดส่ง:</strong><br>
        • ระยะเวลาการจัดส่งเป็นเพียงการประมาณการ<br>
        • เราไม่รับผิดชอบต่อความล่าช้าที่เกิดจากผู้ให้บริการจัดส่ง<br>
        • ค่าจัดส่งคำนวณตามน้ำหนักและปลายทาง<br><br>
        <strong>การคืนสินค้า:</strong><br>
        • สินค้าที่ยังไม่เปิดใช้งานสามารถคืนได้ภายใน 14 วัน<br>
        • สินค้าที่เปิดใช้งาน AI companion แล้วไม่สามารถคืนได้<br>
        • สินค้าต้องอยู่ในสภาพเดิมพร้อมบรรจุภัณฑ์<br>
        • ค่าจัดส่งคืนเป็นความรับผิดชอบของลูกค้า<br>
        • การคืนเงินจะดำเนินการภายใน 7-14 วันทำการ',
        'en' => '<strong>Shipping:</strong><br>
        • Delivery times are estimates only<br>
        • We are not responsible for delays caused by shipping carriers<br>
        • Shipping costs are calculated based on weight and destination<br><br>
        <strong>Returns:</strong><br>
        • Unopened items can be returned within 14 days<br>
        • Items with activated AI companion cannot be returned<br>
        • Products must be in original condition with packaging<br>
        • Return shipping costs are customer\'s responsibility<br>
        • Refunds will be processed within 7-14 business days',
        'cn' => '<strong>运输：</strong><br>
        • 交货时间仅为估计<br>
        • 我们不对运输承运人造成的延误负责<br>
        • 运费根据重量和目的地计算<br><br>
        <strong>退货：</strong><br>
        • 未开封的物品可在14天内退货<br>
        • 已激活AI伴侣的物品无法退货<br>
        • 产品必须保持原始状态并带包装<br>
        • 退货运费由客户承担<br>
        • 退款将在7-14个工作日内处理',
        'jp' => '<strong>配送：</strong><br>
        • 配達時間は見積もりのみです<br>
        • 配送業者による遅延について当社は責任を負いません<br>
        • 配送料は重量と目的地に基づいて計算されます<br><br>
        <strong>返品：</strong><br>
        • 未開封の商品は14日以内に返品できます<br>
        • AIコンパニオンが有効化された商品は返品できません<br>
        • 製品は元の状態で梱包が必要です<br>
        • 返送料はお客様の負担となります<br>
        • 返金は7〜14営業日以内に処理されます',
        'kr' => '<strong>배송：</strong><br>
        • 배송 시간은 추정치일 뿐입니다<br>
        • 당사는 배송 업체로 인한 지연에 대해 책임지지 않습니다<br>
        • 배송비는 무게와 목적지에 따라 계산됩니다<br><br>
        <strong>반품：</strong><br>
        • 개봉하지 않은 품목은 14일 이내에 반품할 수 있습니다<br>
        • AI 컴패니언이 활성화된 품목은 반품할 수 없습니다<br>
        • 제품은 포장과 함께 원래 상태여야 합니다<br>
        • 반송 배송비는 고객 부담입니다<br>
        • 환불은 7-14 영업일 이내에 처리됩니다'
    ],
    'section5_title' => [
        'th' => '5. ทรัพย์สินทางปัญญา',
        'en' => '5. Intellectual Property',
        'cn' => '5. 知识产权',
        'jp' => '5. 知的財産',
        'kr' => '5. 지적 재산권'
    ],
    'section5_content' => [
        'th' => 'เนื้อหาทั้งหมดบนเว็บไซต์นี้ รวมถึงข้อความ กราฟิก โลโก้ รูปภาพ และซอฟต์แวร์ เป็นทรัพย์สินของ AI Companion Perfume และได้รับการคุ้มครองโดยกฎหมายทรัพย์สินทางปัญญา<br><br>
        คุณไม่สามารถ:<br>
        • คัดลอก แก้ไข หรือแจกจ่ายเนื้อหาของเราโดยไม่ได้รับอนุญาต<br>
        • ใช้เนื้อหาเพื่อวัตถุประสงค์ทางการค้า<br>
        • ทำวิศวกรรมย้อนกลับหรือพยายามดึงซอร์สโค้ด<br>
        • ลบหรือเปลี่ยนแปลงประกาศลิขสิทธิ์หรือเครื่องหมายการค้า',
        'en' => 'All content on this website, including text, graphics, logos, images, and software, is the property of AI Companion Perfume and protected by intellectual property laws.<br><br>
        You may not:<br>
        • Copy, modify, or distribute our content without permission<br>
        • Use content for commercial purposes<br>
        • Reverse engineer or attempt to extract source code<br>
        • Remove or alter copyright notices or trademarks',
        'cn' => '本网站上的所有内容，包括文本、图形、徽标、图像和软件，均为AI伴侣香水的财产，并受知识产权法保护。<br><br>
        您不得：<br>
        • 未经许可复制、修改或分发我们的内容<br>
        • 将内容用于商业目的<br>
        • 对软件进行逆向工程或试图提取源代码<br>
        • 删除或更改版权声明或商标',
        'jp' => 'テキスト、グラフィック、ロゴ、画像、ソフトウェアを含む本ウェブサイト上のすべてのコンテンツは、AIコンパニオンフレグランスの財産であり、知的財産法によって保護されています。<br><br>
        以下を行うことはできません：<br>
        • 許可なく当社のコンテンツをコピー、変更、または配布すること<br>
        • 商業目的でコンテンツを使用すること<br>
        • リバースエンジニアリングまたはソースコードの抽出を試みること<br>
        • 著作権表示または商標を削除または変更すること',
        'kr' => '텍스트, 그래픽, 로고, 이미지 및 소프트웨어를 포함한 본 웹사이트의 모든 콘텐츠는 AI 컴패니언 향수의 재산이며 지적 재산권법에 의해 보호됩니다。<br><br>
        다음을 수행할 수 없습니다：<br>
        • 허가 없이 당사의 콘텐츠를 복사, 수정 또는 배포<br>
        • 상업적 목적으로 콘텐츠 사용<br>
        • 리버스 엔지니어링 또는 소스 코드 추출 시도<br>
        • 저작권 고지 또는 상표 제거 또는 변경'
    ],
    'section6_title' => [
        'th' => '6. การจำกัดความรับผิด',
        'en' => '6. Limitation of Liability',
        'cn' => '6. 责任限制',
        'jp' => '6. 責任の制限',
        'kr' => '6. 책임 제한'
    ],
    'section6_content' => [
        'th' => 'เท่าที่กฎหมายอนุญาต AI Companion Perfume จะไม่รับผิดชอบต่อความเสียหายใด ๆ ที่เกิดจากการใช้หรือไม่สามารถใช้บริการของเรา รวมถึงแต่ไม่จำกัดเพียง:<br><br>
        • ความเสียหายทางตรง ทางอ้อม หรือเป็นผลสืบเนื่อง<br>
        • การสูญเสียข้อมูลหรือกำไร<br>
        • การหยุดชะงักของธุรกิจ<br>
        • ความเสียหายต่อชื่อเสียง<br><br>
        ความรับผิดสูงสุดของเราจะไม่เกินจำนวนเงินที่คุณจ่ายสำหรับผลิตภัณฑ์',
        'en' => 'To the extent permitted by law, AI Companion Perfume shall not be liable for any damages arising from the use or inability to use our services, including but not limited to:<br><br>
        • Direct, indirect, or consequential damages<br>
        • Loss of data or profits<br>
        • Business interruption<br>
        • Damage to reputation<br><br>
        Our maximum liability shall not exceed the amount you paid for the product.',
        'cn' => '在法律允许的范围内，AI伴侣香水不对因使用或无法使用我们的服务而产生的任何损害负责，包括但不限于：<br><br>
        • 直接、间接或后果性损害<br>
        • 数据或利润损失<br>
        • 业务中断<br>
        • 声誉损害<br><br>
        我们的最大责任不得超过您为产品支付的金额。',
        'jp' => '法律で許可される範囲において、AIコンパニオンフレグランスは、当社のサービスの使用または使用不能から生じる損害について、以下を含むがこれらに限定されない責任を負いません：<br><br>
        • 直接的、間接的、または結果的な損害<br>
        • データまたは利益の損失<br>
        • 事業の中断<br>
        • 評判への損害<br><br>
        当社の最大責任は、お客様が製品に対して支払った金額を超えないものとします。',
        'kr' => '법이 허용하는 범위 내에서 AI 컴패니언 향수는 당사 서비스의 사용 또는 사용 불가능으로 인해 발생하는 손해에 대해 다음을 포함하되 이에 국한되지 않는 책임을 지지 않습니다：<br><br>
        • 직접적, 간접적 또는 결과적 손해<br>
        • 데이터 또는 이익 손실<br>
        • 비즈니스 중단<br>
        • 평판 손상<br><br>
        당사의 최대 책임은 귀하가 제품에 대해 지불한 금액을 초과하지 않습니다.'
    ],
    'section7_title' => [
        'th' => '7. กฎหมายที่ใช้บังคับ',
        'en' => '7. Governing Law',
        'cn' => '7. 适用法律',
        'jp' => '7. 準拠法',
        'kr' => '7. 준거법'
    ],
    'section7_content' => [
        'th' => 'ข้อกำหนดเหล่านี้อยู่ภายใต้และตีความตามกฎหมายของประเทศไทย ข้อพิพาทใด ๆ ที่เกิดขึ้นจากข้อกำหนดเหล่านี้จะอยู่ในเขตอำนาจของศาลไทย<br><br>
        <strong>ติดต่อเรา:</strong><br><br>
        หากคุณมีคำถามเกี่ยวกับข้อกำหนดเหล่านี้ โปรดติดต่อเรา:<br><br>
        <strong>AI Companion Perfume</strong><br>
        อีเมล: legal@aicompanionperfume.com<br>
        โทรศัพท์: 02-722-7007<br>
        เว็บไซต์: www.aicompanionperfume.com',
        'en' => 'These terms are governed by and construed in accordance with the laws of Thailand. Any disputes arising from these terms shall be subject to the jurisdiction of Thai courts.<br><br>
        <strong>Contact Us:</strong><br><br>
        If you have any questions about these terms, please contact us:<br><br>
        <strong>AI Companion Perfume</strong><br>
        Email: legal@aicompanionperfume.com<br>
        Phone: 02-722-7007<br>
        Website: www.aicompanionperfume.com',
        'cn' => '这些条款受泰国法律管辖并根据泰国法律解释。因这些条款引起的任何争议应受泰国法院管辖。<br><br>
        <strong>联系我们：</strong><br><br>
        如果您对这些条款有任何疑问，请联系我们：<br><br>
        <strong>AI伴侣香水</strong><br>
        电子邮件：legal@aicompanionperfume.com<br>
        电话：02-722-7007<br>
        网站：www.aicompanionperfume.com',
        'jp' => '本規約は、タイの法律に準拠し、タイの法律に従って解釈されます。本規約から生じる紛争は、タイの裁判所の管轄に服するものとします。<br><br>
        <strong>お問い合わせ：</strong><br><br>
        本規約に関するご質問は、以下までお問い合わせください：<br><br>
        <strong>AIコンパニオンフレグランス</strong><br>
        メール：legal@aicompanionperfume.com<br>
        電話：02-722-7007<br>
        ウェブサイト：www.aicompanionperfume.com',
        'kr' => '본 약관은 태국 법률에 따라 규율되고 해석됩니다. 본 약관에서 발생하는 모든 분쟁은 태국 법원의 관할권에 따릅니다。<br><br>
        <strong>문의하기：</strong><br><br>
        본 약관에 대한 질문이 있으시면 다음으로 문의하십시오：<br><br>
        <strong>AI 컴패니언 향수</strong><br>
        이메일：legal@aicompanionperfume.com<br>
        전화：02-722-7007<br>
        웹사이트：www.aicompanionperfume.com'
    ]
];

function tc($key, $lang) {
    global $terms_content;
    return $terms_content[$key][$lang] ?? $terms_content[$key]['en'];
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= tc('title', $lang) ?> - AI Companion Perfume</title>
    
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
        .terms-hero {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .terms-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.5;
        }

        .terms-hero-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .terms-subtitle {
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #ffa719;
            margin-bottom: 20px;
        }

        .terms-title {
            font-size: 56px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .terms-last-updated {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Content Container */
        .terms-container {
            max-width: 900px;
            margin: -40px auto 100px;
            padding: 0 40px;
        }

        .terms-content {
            background: white;
            border-radius: 20px;
            padding: 60px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
        }

        /* Introduction */
        .terms-intro {
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
        .terms-section {
            margin-bottom: 50px;
        }

        .terms-section:last-child {
            margin-bottom: 0;
        }

        .terms-section-title {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .terms-section-content {
            font-size: 16px;
            line-height: 1.9;
            color: #2d2d2d;
        }

        .terms-section-content strong {
            color: #1a1a1a;
            font-weight: 600;
        }

        /* Contact Box */
        .terms-contact-box {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-top: 30px;
        }

        .terms-contact-box strong {
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
            .terms-hero {
                padding: 100px 0 60px;
            }

            .terms-hero-content {
                padding: 0 20px;
            }

            .terms-title {
                font-size: 36px;
            }

            .terms-container {
                padding: 0 20px;
                margin: -30px auto 80px;
            }

            .terms-content {
                padding: 40px 30px;
            }

            .terms-section-title {
                font-size: 22px;
            }

            .terms-intro,
            .terms-section-content {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="terms-hero">
        <div class="terms-hero-content">
            <p class="terms-subtitle">Legal</p>
            <h1 class="terms-title"><?= tc('title', $lang) ?></h1>
            <p class="terms-last-updated"><?= tc('last_updated', $lang) ?></p>
        </div>
    </section>

    <!-- Content -->
    <div class="terms-container">
        <div class="terms-content">
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
            <div class="terms-intro">
                <?= tc('intro', $lang) ?>
            </div>

            <!-- Section 1 -->
            <div class="terms-section">
                <h2 class="terms-section-title"><?= tc('section1_title', $lang) ?></h2>
                <div class="terms-section-content">
                    <?= tc('section1_content', $lang) ?>
                </div>
            </div>

            <!-- Section 2 -->
            <div class="terms-section">
                <h2 class="terms-section-title"><?= tc('section2_title', $lang) ?></h2>
                <div class="terms-section-content">
                    <?= tc('section2_content', $lang) ?>
                </div>
            </div>

            <!-- Section 3 -->
            <div class="terms-section">
                <h2 class="terms-section-title"><?= tc('section3_title', $lang) ?></h2>
                <div class="terms-section-content">
                    <?= tc('section3_content', $lang) ?>
                </div>
            </div>

            <!-- Section 4 -->
            <div class="terms-section">
                <h2 class="terms-section-title"><?= tc('section4_title', $lang) ?></h2>
                <div class="terms-section-content">
                    <?= tc('section4_content', $lang) ?>
                </div>
            </div>

            <!-- Section 5 -->
            <div class="terms-section">
                <h2 class="terms-section-title"><?= tc('section5_title', $lang) ?></h2>
                <div class="terms-section-content">
                    <?= tc('section5_content', $lang) ?>
                </div>
            </div>

            <!-- Section 6 -->
            <div class="terms-section">
                <h2 class="terms-section-title"><?= tc('section6_title', $lang) ?></h2>
                <div class="terms-section-content">
                    <?= tc('section6_content', $lang) ?>
                </div>
            </div>

            <!-- Section 7 - Governing Law & Contact -->
            <div class="terms-section">
                <h2 class="terms-section-title"><?= tc('section7_title', $lang) ?></h2>
                <div class="terms-section-content">
                    <?= tc('section7_content', $lang) ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'template/footer.php' ?>

</body>
</html>