<?php
require_once('lib/connect.php');

session_start();
$lang = 'th';

if (isset($_GET['lang'])) {
    $supportedLangs = ['th', 'en', 'cn', 'jp', 'kr'];
    $newLang = $_GET['lang'];
    if (in_array($newLang, $supportedLangs)) {
        $_SESSION['lang'] = $newLang;
        $lang = $newLang;
    } else {
        unset($_SESSION['lang']);
    }
} elseif (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}
$contentTranslations = [
    'title' => [
        'th' => 'INSUL Acoustics Software: มาตรฐานสูงสุดสำหรับบริการด้านอะคูสติก',
        'en' => 'INSUL Acoustics Software: The Ultimate Standard for Acoustic Services',
        'cn' => 'INSUL声学软件：声学服务的终极标准',
        'jp' => 'INSUL音響ソフトウェア：音響サービスのための究極の標準',
        'kr' => 'INSUL 음향 소프트웨어: 음향 서비스를 위한 최고의 표준'
    ],
    'section1_text1' => [
        'th' => 'จากประสบการณ์ด้านการวิจัยและพัฒนา รวมถึงการเป็นที่ปรึกษาด้านอะคูสติกอย่างเชี่ยวชาญ บริษัท แทรนดาร์ อะคูสติกส์ พบว่าแม้จะใช้<br>วัสดุที่มีคุณภาพสูงในการติดตั้งหรือก่อสร้าง ก็ยังไม่สามารถตอบโจทย์ในด้านประสิทธิภาพที่ลูกค้าต้องการได้อย่างแท้จริง',
        'en' => 'Based on our experience in Research and Development and as expert acoustic consultants, perfume Acoustics has found that even using high-quality materials for installation or construction does not always meet the efficiency that clients truly need.',
        'cn' => '根据我们在研发和作为专业声学顾问的经验，perfume Acoustics 发现，即使使用高质量的材料进行安装或建造，也未必能真正满足客户所需的效率。',
        'jp' => '研究開発と専門の音響コンサルタントとしての経験から、perfume Acousticsは、高品質な材料を設置や建設に使用しても、お客様が本当に必要とする効率を常に満たすわけではないことを発見しました。',
        'kr' => '연구 및 개발, 그리고 전문 음향 컨설턴트로서의 경험을 바탕으로, perfume Acoustics는 고품질의 재료를 설치나 건축에 사용하더라도 고객이 진정으로 필요로 하는 효율성을 항상 충족시키지는 못한다는 것을 발견했습니다.'
    ],
    'section1_text2' => [
        'th' => 'นี่จึงเป็นเหตุผลที่ perfume Acoustics ได้นำเสนอบริการซอฟต์แวร์นั่นก็คือโปรแกรม INSUL ที่จะทำให้ลูกค้าเข้าใจถึงค่าระดับเสียงเดิมและค่าอะคูสติกที่ทำได้ เพื่อป้องกันความผิดพลาดในการเลือกวัสดุที่ต้องเสียค่าใช้จ่ายสูง',
        'en' => 'This is why perfume Acoustics introduced a software service: the INSUL program. It allows clients to understand the original sound levels and the acoustic values that can be achieved, helping to prevent costly mistakes in material selection.',
        'cn' => '这就是 perfume Acoustics 推出 INSUL 软件服务的原因。它能让客户了解原始声级和可达到的声学值，有助于避免因材料选择错误而造成的昂贵失误。',
        'jp' => 'これが、perfume Acousticsがソフトウェアサービス「INSULプログラム」を導入した理由です。これにより、お客様は元の騒音レベルと達成可能な音響値を理解することができ、高価な材料選択における費用のかかるミスを防ぐのに役立ちます。',
        'kr' => '이것이 perfume Acoustics가 INSUL 소프트웨어 서비스를 도입한 이유입니다. 이 프로그램을 통해 고객은 원래의 소음 수준과 달성 가능한 음향 값을 이해할 수 있어, 비용이 많이 드는 재료 선택 실수를 방지하는 데 도움이 됩니다.'
    ],
    'section1_text3' => [
        'th' => 'INSUL คือซอฟต์แวร์จำลองอะคูสติกที่สะดวก รวดเร็ว และแม่นยำในการคำนวณค่าต่างๆ ส่วนใหญ่ใช้ในการคาดการณ์ประสิทธิภาพของการกันเสียงในระบบผนัง และสามารถกำหนดคุณสมบัติของวัสดุต่างๆ ได้อย่างมีประสิทธิภาพ เป็นโปรแกรมคำนวณการป้องกันเสียงของผนัง พื้น ฝ้าเพดาน และหน้าต่าง ที่แสดงค่าการสูญเสียในย่านความถี่ 1/3 OCTAVE BANDS และค่าดัชนีลดทอนเสียงแบบถ่วงน้ำหนัก (STC หรือ Rw) สำหรับการคำนวณการป้องกันเสียง สามารถเปลี่ยนวัสดุหรือดีไซน์ได้อย่างง่ายดาย สะดวก รวดเร็ว และแม่นยำในการคำนวณค่าต่างๆ',
        'en' => 'INSUL is an acoustic simulation software that is convenient, fast, and accurate for calculating various values. It is mostly used to predict the sound insulation performance of wall systems and can effectively define the properties of different materials. It is a program that calculates the sound insulation of walls, floors, ceilings, and windows. It displays the loss values in 1/3 OCTAVE BANDS frequencies and the Weighted Sound Reduction Index (STC or Rw). For sound insulation calculations, materials or designs can be changed easily. It is convenient, fast, and accurate in calculating various values.',
        'cn' => 'INSUL 是一款方便、快速且精确的声学模拟软件，用于计算各种数值。它主要用于预测墙体系统的隔音性能，并能有效地定义不同材料的属性。该程序计算墙壁、地板、天花板和窗户的隔音效果，并在 1/3 倍频程带中显示损耗值和加权隔音指数（STC 或 Rw）。对于隔音计算，可以轻松更改材料或设计。它在计算各种数值时既方便又快速且精确。',
        'jp' => 'INSULは、様々な値を便利、迅速、かつ正確に計算する音響シミュレーションソフトウェアです。主に壁システムの遮音性能を予測するために使用され、異なる材料の特性を効果的に定義することができます。これは、壁、床、天井、窓の遮音を計算するプログラムで、1/3 OCTAVE BANDS周波数での損失値と加重遮音指標（STCまたはRw）を表示します。遮音計算のため、材料やデザインを簡単に変更でき、様々な値を便利、迅速、かつ正確に計算します。',
        'kr' => 'INSUL은 다양한 값을 편리하고 빠르며 정확하게 계산하는 음향 시뮬레이션 소프트웨어입니다. 주로 벽 시스템의 차음 성능을 예측하는 데 사용되며, 다양한 재료의 속성을 효과적으로 정의할 수 있습니다. 이는 벽, 바닥, 천장 및 창문의 차음을 계산하는 프로그램으로, 1/3 옥타브 밴드 주파수에서의 손실 값과 가중 음향 저감 지수(STC 또는 Rw)를 표시합니다. 차음 계산을 위해 재료나 디자인을 쉽게 변경할 수 있으며, 다양한 값을 편리하고 빠르고 정확하게 계산합니다.'
    ],
    'section1_text4' => [
        'th' => 'ปัจจุบัน INSUL ถือเป็นซอฟต์แวร์ที่ได้รับความนิยมในหมู่ผู้ผลิตแผ่นยิปซัม แผ่นฝ้า และผนัง ทุกรายต่างเลือกใช้ แล้วมาดูกันว่าโปรแกรม INSUL มีประโยชน์อย่างไรกับคุณบ้าง!',
        'en' => 'Today, INSUL is considered a popular software among all manufacturers of gypsum boards, ceiling panels, and walls, who have chosen to use it. Let\'s talk about how the INSUL program can benefit you!',
        'cn' => '如今，INSUL 被所有石膏板、天花板和墙壁制造商视为一款受欢迎的软件，他们都选择使用它。让我们来谈谈 INSUL 程序能为您带来哪些好处！',
        'jp' => '今日、INSULは石膏ボード、天井パネル、壁のすべてのメーカーの間で人気のソフトウェアと見なされており、彼らはそれを使用することを選択しました。INSULプログラムがあなたにどのような利益をもたらすかについて話しましょう！',
        'kr' => '오늘날 INSUL은 모든 석고 보드, 천장 패널, 벽 제조업체 사이에서 인기 있는 소프트웨어로 간주되며, 그들이 선택하여 사용하고 있습니다. INSUL 프로그램이 당신에게 어떤 이점을 줄 수 있는지 이야기해 봅시다!'
    ],
    'section2_title' => [
        'th' => '5 ประโยชน์อันโดดเด่นของโปรแกรม INSUL เพื่อการใช้งานด้านอะคูสติก',
        'en' => '5 Excellent Benefits of the INSUL Program for Acoustic Applications',
        'cn' => 'INSUL 程序在声学应用方面的 5 大卓越优势',
        'jp' => '音響アプリケーションのためのINSULプログラムの5つの優れた利点',
        'kr' => '음향 애플리케이션을 위한 INSUL 프로그램의 5가지 뛰어난 이점'
    ],
    'section2_benefit1_title' => [
        'th' => '1. สร้างรายการค่าการสูญเสียการส่งผ่านเสียงของเสียงในอากาศและเสียงฝน',
        'en' => '1. Creates a list of transmission loss for airborne and rain sound.',
        'cn' => '1. 创建空气声和雨声的传输损耗列表。',
        'jp' => '1. 空気音と雨音の伝達損失のリストを作成します。',
        'kr' => '1. 공기음 및 빗소리의 투과 손실 목록을 생성합니다.'
    ],
    'section2_benefit1_text' => [
        'th' => 'INSUL เป็นโปรแกรมสำหรับคำนวณการป้องกันเสียงของผนัง พื้น ฝ้าเพดาน หน้าต่าง ตลอดจนเสียงกระทบและเสียงฝนตกบนพื้นและหลังคา โปรแกรมสามารถคำนวณค่าที่เรียกว่า Transmission Loss (TL) หรือ Impact Sound (Ln) ในย่านความถี่ 1/3 octave bands และแสดงค่าเปรียบเทียบ เช่น STC หรือ Rw หรือ Impact Rating (IIC / LnTw) เพื่อวิเคราะห์คุณภาพของระบบกันเสียงที่เกี่ยวข้องกับเสียงรบกวน เสียงฝนตก เสียงอาบแดด หรือข้อบังคับด้านเสียง',
        'en' => 'INSUL is a program for calculating the sound insulation of walls, floors, ceilings, windows, as well as impact and rain sound on floors and roofs. The program can calculate values known as Transmission Loss (TL) or Impact Sound (Ln) in 1/3 octave bands and display comparative values such as STC or Rw or Impact Rating (IIC / LnTw) to analyze the quality of the sound insulation system regarding noise, rain noise, sunbathing, or sound regulations.',
        'cn' => 'INSUL 是一款用于计算墙壁、地板、天花板、窗户以及地板和屋顶上的撞击声和雨声的隔音效果的程序。该程序可以在 1/3 倍频程带中计算称为传输损耗 (TL) 或撞击声 (Ln) 的值，并显示比较值，如 STC 或 Rw 或撞击评级 (IIC / LnTw)，以分析隔音系统在噪音、雨声、日光浴或声音法规方面的质量。',
        'jp' => 'INSULは、壁、床、天井、窓、さらには床や屋根への衝撃音や雨音の遮音性を計算するためのプログラムです。このプログラムは、1/3オクターブバンドで伝達損失（TL）または衝撃音（Ln）として知られる値を計算し、STCやRw、衝撃評価（IIC / LnTw）などの比較値を表示して、騒音、雨音、日光浴、または音響規制に関連する遮音システムの品質を分析します。',
        'kr' => 'INSUL은 벽, 바닥, 천장, 창문뿐만 아니라 바닥과 지붕의 충격음 및 빗소리 차음을 계산하는 프로그램입니다. 이 프로그램은 1/3 옥타브 밴드에서 투과 손실(TL) 또는 충격음(Ln)으로 알려진 값을 계산하고 STC 또는 Rw 또는 충격 등급(IIC / LnTw)과 같은 비교 값을 표시하여 소음, 빗소리, 일광욕 또는 소음 규제와 관련된 차음 시스템의 품질을 분석합니다.'
    ],
    'section2_benefit2_title' => [
        'th' => '2. ประเมินวัสดุหรือการเปลี่ยนแปลงการออกแบบ',
        'en' => '2. Evaluate material or design changes',
        'cn' => '2. 评估材料或设计更改',
        'jp' => '2. 材料や設計の変更を評価する',
        'kr' => '2. 재료 또는 디자인 변경 사항 평가'
    ],
    'section2_benefit2_text' => [
        'th' => 'INSUL สามารถใช้เพื่อประเมินวัสดุและระบบใหม่ๆ ได้อย่างรวดเร็ว หรือตรวจสอบผลกระทบของการเปลี่ยนแปลงในการออกแบบที่มีอยู่แล้วในรูปแบบของวัสดุ โดยใช้กราฟสำหรับแผ่นยืดหยุ่นที่รู้จักกันดี รวมถึงค่าที่เหมาะสมสำหรับเอฟเฟกต์แผงหนาตามที่ Ljunggren, Rindell และคนอื่นๆ เผยแพร่ พารามิเตอร์ที่ซับซ้อนมากขึ้นจะถูกจำลองโดยใช้ผลงานของ Sharp, Cremer และคนอื่นๆ',
        'en' => 'INSUL can be used to quickly evaluate new materials and systems or check the impact of changes in existing designs as a material model. It uses graphs for well-known flexible sheets, as well as values suitable for thick panel effects as published by Ljunggren, Rindell, and others. More complex parameters are simulated using the work of Sharp, Cremer, and others.',
        'cn' => 'INSUL 可用于快速评估新材料和系统，或以材料模型形式检查现有设计中的更改影响。它使用众所周知的柔性板的图表，以及由 Ljunggren、Rindell 等人发布的适用于厚板效果的值。更复杂的参数则通过使用 Sharp、Cremer 等人的研究成果进行模拟。',
        'jp' => 'INSULは、新しい材料やシステムを迅速に評価したり、材料モデルとして既存のデザインの変更の影響をチェックしたりするために使用できます。これは、よく知られている柔軟なシートのグラフや、Ljunggren、Rindellなどが発表した厚いパネル効果に適した値を使用します。より複雑なパラメータは、Sharp、Cremerなどの研究成果を使用してシミュレーションされます。',
        'kr' => 'INSUL은 새로운 재료와 시스템을 신속하게 평가하거나, 재료 모델로서 기존 디자인의 변경 영향을 확인하는 데 사용될 수 있습니다. 이는 잘 알려진 유연한 시트의 그래프와 Ljunggren, Rindell 등이 발표한 두꺼운 패널 효과에 적합한 값을 사용합니다. 더 복잡한 매개변수는 Sharp, Cremer 등의 연구를 사용하여 시뮬레이션됩니다.'
    ],
    'section2_benefit3_title' => [
        'th' => '3. การพัฒนาอย่างต่อเนื่อง',
        'en' => '3. Continuous Development',
        'cn' => '3. 持续发展',
        'jp' => '3. 継続的な開発',
        'kr' => '3. 지속적인 개발'
    ],
    'section2_benefit3_text1' => [
        'th' => 'INSUL เปิดให้บริการมานานกว่า 15 ปีและได้รับการปรับปรุงอย่างมีนัยสำคัญในช่วงเวลานี้ ได้รับการพัฒนาหลายเวอร์ชันและกลายเป็นเครื่องมือที่ใช้งานง่ายและมีประโยชน์ซึ่งรองรับทั้งระบบ Windows และ Mac และได้รับการปรับปรุงอย่างต่อเนื่องโดยการเปรียบเทียบกับข้อมูลการทดสอบในห้องปฏิบัติการเพื่อให้ได้ความแม่นยำที่ยอมรับได้สำหรับโครงการจริงที่หลากหลาย',
        'en' => 'INSUL has been available for over 15 years and has been significantly improved during this time. It has been developed in several versions and has become an easy-to-use and useful tool that supports both Windows and Mac systems. It has also been continuously improved by comparing it with laboratory test data to achieve acceptable accuracy for a wide variety of real projects.',
        'cn' => 'INSUL 已经上市超过 15 年，并在此期间得到了显著改进。它已经开发了多个版本，并成为一个易于使用和有用的工具，支持 Windows 和 Mac 系统。通过与实验室测试数据进行比较，它也得到了持续改进，以在各种实际项目中获得可接受的准确性。',
        'jp' => 'INSULは15年以上にわたり利用可能であり、この間に大幅に改善されました。いくつかのバージョンが開発され、WindowsとMacの両方のシステムをサポートする使いやすく有用なツールになりました。また、実際のさまざまなプロジェクトで許容できる精度を達成するために、ラボテストデータとの比較により継続的に改善されています。',
        'kr' => 'INSUL은 15년 이상 사용 가능했으며 이 기간 동안 크게 개선되었습니다. 여러 버전으로 개발되어 Windows와 Mac 시스템을 모두 지원하는 사용하기 쉽고 유용한 도구가 되었습니다. 또한 다양한 실제 프로젝트에서 수용 가능한 정확도를 얻기 위해 실험실 테스트 데이터와 비교하여 지속적으로 개선되었습니다.'
    ],
    'section2_benefit3_text2' => [
        'th' => 'ข้อมูลการทดสอบสามารถป้อนได้ง่ายสำหรับการเปรียบเทียบกับการคาดการณ์ และโครงสร้างสามารถบันทึกเพื่อเรียกใช้ในภายหลังได้',
        'en' => 'Test data can be easily entered for comparison with predictions, and structures can be saved to be recalled later.',
        'cn' => '测试数据可以轻松输入以与预测进行比较，并且可以保存结构以便以后调用。',
        'jp' => 'テストデータは予測と比較するために簡単に入力でき、構造は後で呼び出すために保存できます。',
        'kr' => '예측과 비교하기 위해 테스트 데이터를 쉽게 입력할 수 있으며, 구조는 나중에 다시 불러올 수 있도록 저장할 수 있습니다.'
    ],
    'section2_benefit4_title' => [
        'th' => '4. กำหนดโครงสร้างได้อย่างรวดเร็วและมั่นใจ',
        'en' => '4. Specify constructions with speed and confidence',
        'cn' => '4. 快速自信地指定结构',
        'jp' => '4. 迅速かつ自信を持って構造を特定する',
        'kr' => '4. 빠르고 자신있게 구조 지정'
    ],
    'section2_benefit4_text1' => [
        'th' => 'INSUL คำนึงถึงผลกระทบของขนาดและข้อจำกัดของพื้นที่ซึ่งมีความสำคัญอย่างยิ่งในการคาดการณ์สำหรับพื้นที่ขนาดเล็ก เช่น หน้าต่าง และสำหรับส่วนประกอบความถี่ต่ำทั่วไป',
        'en' => 'INSUL takes into account the effects of size and spatial limitations, which are very important when making predictions for small areas, such as windows, and also for typical low-frequency components.',
        'cn' => 'INSUL 考虑了尺寸和空间限制的影响，这在对小面积（如窗户）和典型的低频组件进行预测时非常重要。',
        'jp' => 'INSULは、窓などの小さな領域や一般的な低周波コンポーネントの予測を行う際に非常に重要な、サイズと空間的制限の影響を考慮に入れます。',
        'kr' => 'INSUL은 크기와 공간적 제약의 영향을 고려하며, 이는 창문과 같은 작은 영역 및 일반적인 저주파수 구성 요소에 대한 예측을 할 때 매우 중요합니다.'
    ],
    'section2_benefit4_text2' => [
        'th' => 'อย่างไรก็ตาม INSUL ไม่สามารถใช้สำหรับการวัดได้ อย่างไรก็ตามเมื่อเปรียบเทียบกับข้อมูลการทดสอบบ่งชี้ว่า INSUL สามารถคาดการณ์ค่า STC ได้อย่างน่าเชื่อถือภายใน 3dB ในโครงสร้างขนาดใหญ่ สิ่งนี้ช่วยเพิ่มความสามารถของทีมออกแบบอะคูสติกและผู้ผลิตผลิตภัณฑ์ในการกำหนดโครงสร้างได้อย่างรวดเร็วและมั่นใจสำหรับใช้ในเอกสารที่จำเป็น',
        'en' => 'However, INSUL cannot be used for measurement. Nevertheless, in comparison to test data, it indicates that INSUL can reliably predict STC values within 3dB in large-scale constructions. This helps to enhance the ability of acoustic design teams and product manufacturers to quickly and confidently specify constructions for use in required documentation.',
        'cn' => '然而，INSUL 不能用于测量。尽管如此，与测试数据进行比较表明，INSUL 可以在大型结构中可靠地预测 STC 值在 3dB 以内。这有助于增强声学设计团队和产品制造商的能力，使其能够快速、自信地指定所需文档中使用的结构。',
        'jp' => 'ただし、INSULは測定には使用できません。それにもかかわらず、テストデータとの比較では、INSULが大規模な構造で3dB以内のSTC値を信頼性高く予測できることが示されています。これにより、音響設計チームと製品メーカーが、必要な文書で使用する構造を迅速かつ自信を持って指定する能力が向上します。',
        'kr' => '그러나 INSUL은 측정에 사용될 수 없습니다. 그럼에도 불구하고, 테스트 데이터와 비교했을 때 INSUL이 대규모 구조물에서 3dB 이내의 STC 값을 신뢰성 있게 예측할 수 있음을 나타냅니다. 이는 음향 설계 팀과 제품 제조업체가 필요한 문서에 사용할 구조물을 빠르고 자신있게 지정하는 능력을 향상시키는 데 도움이 됩니다.'
    ],
    'section2_benefit5_title' => [
        'th' => '5. มีหลายภาษาให้เลือกใช้',
        'en' => '5. Available in multiple languages',
        'cn' => '5. 提供多种语言版本',
        'jp' => '5. 複数の言語で利用可能',
        'kr' => '5. 여러 언어로 사용 가능'
    ],
    'section2_benefit5_text' => [
        'th' => 'INSUL สามารถเปลี่ยนการแสดงข้อมูลเป็นภาษาอังกฤษ ฝรั่งเศส เยอรมัน โปแลนด์ สเปน สวีเดน หรือภาษาอื่นๆ ได้ โดยการแปลนั้นดำเนินการโดยผู้เชี่ยวชาญด้านอะคูสติกเพื่อให้เหมาะสมที่สุดสำหรับคำศัพท์ทางเทคนิคที่ใช้กันทั่วไปในแต่ละประเทศ',
        'en' => 'INSUL can be changed to display information in English, French, German, Polish, Spanish, Swedish, or other languages. The translations are done by acoustic specialists to be as suitable as possible for the technical terminology commonly used in each country.',
        'cn' => 'INSUL 可以更改为显示英语、法语、德语、波兰语、西班牙语、瑞典语或其他语言的信息。翻译由声学专家完成，以尽可能适合各国普遍使用的技术术语。',
        'jp' => 'INSULは、英語、フランス語、ドイツ語、ポーランド語、スペイン語、スウェーデン語、またはその他の言語で情報を表示するように変更できます。翻訳は、各国の技術用語にできるだけ適するように、音響専門家によって行われます。',
        'kr' => 'INSUL은 영어, 프랑스어, 독일어, 폴란드어, 스페인어, 스웨덴어 또는 기타 언어로 정보를 표시하도록 변경할 수 있습니다. 번역은 각 나라에서 일반적으로 사용되는 기술 용어에 가장 적합하도록 음향 전문가에 의해 이루어집니다.'
    ],
    'conclusion1' => [
        'th' => 'เสียงรบกวนเป็นปัญหาสำคัญที่มักเกิดขึ้นในทุกที่ ไม่ว่าจะเป็นในอาคารสำนักงาน พื้นที่พักอาศัย หรือแม้แต่ในโรงงานอุตสาหกรรม เป็นความเสี่ยงที่ส่งผลกระทบในด้านลบต่อการทำงานเป็นอย่างมาก โดยเฉพาะในโรงงานอุตสาหกรรมซึ่งเป็นสถานที่ทำงานที่มีเสียงดังมากที่สุด การดำเนินงานของเครื่องจักรบางประเภทอาจเป็นอันตรายต่อพนักงานได้ หากไม่มีมาตรการควบคุม',
        'en' => 'Noise is a significant problem that often occurs everywhere, whether in office buildings, residential areas, or even in industrial factories. It is a risk that has a very negative impact on work. Especially in industrial factories, where it is the noisiest workplace, some machinery operations can be dangerous to employees if there are no regulatory measures in place.',
        'cn' => '噪音是一个无处不在的重大问题，无论是在办公楼、住宅区，甚至在工业工厂中。它对工作产生非常负面的影响。特别是在工业工厂，这是最嘈杂的工作场所，如果没有适当的监管措施，某些机械操作可能对员工造成危险。',
        'jp' => '騒音は、オフィスビル、住宅地、あるいは工業工場など、あらゆる場所で頻繁に発生する重大な問題です。これは、作業に非常に悪影響を及ぼすリスクです。特に工業工場は最も騒々しい作業場であり、規制措置が講じられていない場合、一部の機械操作は従業員にとって危険である可能性があります。',
        'kr' => '소음은 사무실 건물, 주거 지역, 심지어 산업 공장에서도 흔히 발생하는 중요한 문제입니다. 이는 업무에 매우 부정적인 영향을 미치는 위험 요소입니다. 특히 가장 시끄러운 작업장인 산업 공장에서는 규제 조치가 마련되지 않으면 일부 기계 작동이 직원에게 위험할 수 있습니다.'
    ],
    'conclusion2' => [
        'th' => 'หากคุณต้องการซอฟต์แวร์ที่เข้าใจง่าย ใช้งานง่าย และมีประสิทธิภาพ perfume Acoustics ขอเสนอบริการ INSUL Acoustics Software เพื่อให้คุณวางใจ ปลอดภัยไร้กังวล และไม่ต้องแก้ไขหรือสร้างโครงสร้างใหม่ในอนาคต',
        'en' => 'If you want software that is easy to understand, easy to use, and effective, perfume Acoustics offers the INSUL Acoustics Software service to provide you with peace of mind, without worry, and without having to fix or create new structures in the future.',
        'cn' => '如果您想要一款易于理解、易于使用且有效的软件，perfume Acoustics 提供的 INSUL 声学软件服务将让您高枕无忧，无需担心未来需要修复或重建结构。',
        'jp' => 'もしあなたが理解しやすく、使いやすく、効果的なソフトウェアを求めているなら、perfume AcousticsはINSUL音響ソフトウェアサービスを提供し、将来的に構造を修正したり新しく作成したりする必要なく、安心と安全を提供します。',
        'kr' => '이해하기 쉽고, 사용하기 쉬우며, 효과적인 소프트웨어를 원하신다면, perfume Acoustics는 INSUL 음향 소프트웨어 서비스를 제공하여 미래에 구조를 수정하거나 새로 만들 필요 없이 안심하고 안전하게 작업할 수 있도록 도와드립니다.'
    ],
    'image_source' => [
        'th' => 'ที่มารูปภาพ: Insul',
        'en' => 'Image source: Insul',
        'cn' => '图片来源：Insul',
        'jp' => '画像出典：Insul',
        'kr' => '이미지 출처: Insul'
    ]
];

function getTextByLang($key) {
    global $contentTranslations, $lang;
    return $contentTranslations[$key][$lang] ?? $contentTranslations[$key]['th'];
}

$contentSections = [
    ['text' => '<h2 style="font-size: 28px; font-weight: bold;">' . getTextByLang('title') . '</h2>'],
    ['text' => getTextByLang('section1_text1')],
    ['text' => getTextByLang('section1_text2')],
    ['image' => '../public/img/acoustics.jpg'],
    ['text' => getTextByLang('section1_text3')],
    ['text' => getTextByLang('section1_text4')],
    ['text' => '<h2 style="font-size: 28px; font-weight: bold;">' . getTextByLang('section2_title') . '</h2>'],
    ['text' => '1. '.getTextByLang('section2_benefit1_title')],
    ['text' => getTextByLang('section2_benefit1_text')],
    ['image' => '../public/img/insul1.jpg'],
    ['text' => '<p style="border-top: 2px; padding-top: 12px; margin-top: 24px;">' . getTextByLang('section2_benefit2_title') . '</p>'],
    ['text' => getTextByLang('section2_benefit2_text')],
    ['text' => '3. '.getTextByLang('section2_benefit3_title')],
    ['text' => getTextByLang('section2_benefit3_text1')],
    ['text' => getTextByLang('section2_benefit3_text2')],
    ['image' => '../public/img/insul2.jpg'],
    ['text' => '<p style="border-top: 2px; padding-top: 12px; margin-top: 24px;">' . getTextByLang('section2_benefit4_title') . '</p>'],
    ['text' => getTextByLang('section2_benefit4_text1')],
    ['text' => getTextByLang('section2_benefit4_text2')],
    ['text' => '5. '.getTextByLang('section2_benefit5_title')],
    ['text' => getTextByLang('section2_benefit5_text')],
    ['text' => getTextByLang('conclusion1')],
    ['text' => getTextByLang('conclusion2')],
    ['text' => '<p style="border-top: 2px; padding-top: 12px; margin-top: 24px;">' . getTextByLang('image_source') . '</p>'],
];

?>
<!DOCTYPE html>
<html>
<head>
    <html lang="<?= htmlspecialchars($lang) ?>">
    <?php include 'template/header.php' ?>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
     <ul id="flag-dropdown-list" class="flag-dropdown" style="left: 74%;">
        </ul>
    <?php include 'template/navbar_slide.php' ?>

    <?php
    $content = '<div class="content-sticky" id="page_about">';
    $content .= '<div class="container" style="max-width: 90%;">';
    $content .= '<div class="box-content">';

    foreach ($contentSections as $section) {
        $content .= '<div class="row">';

        if (isset($section['image'])) {
            $content .= '<div class="col-md-6">';
            $content .= '<img style="width: 100%;" src="' . $section['image'] . '" alt="">';
            $content .= '</div>';
        }

        if (isset($section['texts']) && is_array($section['texts'])) {
            $content .= '<div class="col-md-' . (isset($section['image']) ? '6' : '12') . '">';
            $content .= '<div class="d-flex justify-content-between">';
            foreach ($section['texts'] as $text) {
                $content .= '<div style="width: 32%; padding: 0 10px;">' . $text . '</div>';
            }
            $content .= '</div></div>';
            $content .= '<div class="col-12"><hr></div>';
        }

        if (isset($section['quote'])) {
            $quote = $section['quote'];
            $content .= '
            <div style="text-align: center; padding: 40px 20px; font-style: italic; font-size: 25px; position: relative; width: 100%;">
                <div style="font-size: 40px; color: #ccc; position: absolute; left: 10px; top: 0;">“</div>
                <p style="margin: 0 40px;">' . $quote['text'] . '</p>
                <div style="margin-top: 20px; font-style: normal;">
                    <strong>' . $quote['author'] . '</strong><br>' . $quote['position'] . '
                </div>
            </div>';
        }

        if (isset($section['text']) && !isset($section['texts'])) {
            $content .= '<div class="col-md-' . (isset($section['image']) ? '6' : '12') . '">';
            $content .= '<p>' . $section['text'] . '</p>';
            $content .= '</div>';
        }

        $content .= '</div>';

        if (isset($section['image'])) {
        }
    }

    $content .= '</div>';
    $content .= '</div>';
    $content .= '</div>';

    echo $content;
    ?>

    <?php include 'template/footer.php' ?>

    <script src="index_.js?v=<?php echo time(); ?>"></script>
    <script src="app/js/news/news_.js?v=<?php echo time(); ?>"></script>

</body>
</html>