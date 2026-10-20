<?php

namespace Fuel\Tasks;

/**
 * Insert newspaper-style posts: long copy with photos placed between paragraphs.
 *
 * Usage:
 * php oil r seedblogs
 * php oil r seedblogs 4
 */
class Seedblogs
{
	protected static $photo_bytes = array();

	/**
	 * @param int|string|null $count Number of posts to insert
	 * @return string
	 */
	public static function run($count = 4)
	{
		$count = (int) $count;
		if ($count < 1)
		{
			$count = 1;
		}

		$stories = static::stories();
		$user = \Model_User::query()->order_by('id', 'asc')->get_one();
		$created = 0;

		for ($number = 1; $number <= $count; $number++)
		{
			$story = $stories[($number - 1) % count($stories)];
			$cycle = (int) floor(($number - 1) / count($stories)) + 1;
			$title = $story['title'];
			if ($cycle > 1)
			{
				$title .= ' (kỳ '.$cycle.')';
			}

			$category = \Model_Category::query()->where('name', $story['category'])->get_one();
			if ($category === null)
			{
				$category = \Model_Category::query()->order_by('id', 'asc')->get_one();
			}

			$blog = \Model_Blog::forge(array(
				'title' => $title,
				'category_id' => $category ? (int) $category->id : null,
				'user_id' => $user ? (int) $user->id : null,
			));
			$blog->save();

			$stamp = date('Y-m-d H:i:s', time() - ($number * 7200));
			\DB::update('blogs')
				->set(array(
					'created' => $stamp,
					'modified' => $stamp,
				))
				->where('id', (int) $blog->id)
				->execute();

			foreach ($story['children'] as $offset => $child_story)
			{
				$child = \Model_Blog_Child::forge(array(
					'blog_id' => (int) $blog->id,
					'position' => $offset + 1,
				));
				$child->save();

				$body = static::render_child((int) $blog->id, (int) $child->id, $child_story);
				$content = \Model_Blog_Content::forge(array(
					'blog_child_id' => (int) $child->id,
					'position' => 1,
					'body' => $body,
					'image' => '',
				));
				$content->save();
			}

			$created++;
			\Cli::write('Created blog #'.$blog->id.' — '.$title, 'green');
		}

		return 'Seeded '.$created.' blog post'.($created === 1 ? '' : 's').'.';
	}

	/**
	 * Turn one child story into HTML with photos between the paragraphs.
	 *
	 * @param int $blog_id
	 * @param int $child_id
	 * @param array $child_story
	 * @return string
	 */
	protected static function render_child($blog_id, $child_id, array $child_story)
	{
		$html = '';
		$photo_slot = 0;

		if ($child_story['heading'] !== '')
		{
			$html .= '<h2>'.static::text($child_story['heading']).'</h2>';
		}

		foreach ($child_story['blocks'] as $block)
		{
			if ($block['type'] === 'image')
			{
				$photo_slot++;
				$relative = static::store_photo($block['photo'], $blog_id, $child_id, $photo_slot);
				$html .= '<p><img src="/'.static::text($relative).'" alt=""></p>';
				continue;
			}

			if ($block['type'] === 'quote')
			{
				$html .= '<blockquote><p>'.static::text($block['text']).'</p></blockquote>';
				continue;
			}

			$html .= '<p>'.static::text($block['text']).'</p>';
		}

		return $html;
	}

	/**
	 * @param string $value
	 * @return string
	 */
	protected static function text($value)
	{
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Save a stock photo, or a plain stand-in when the download fails.
	 *
	 * @param int $photo_id
	 * @param int $blog_id
	 * @param int $child_id
	 * @param int $slot 1, 2, or 3
	 * @return string Relative public path
	 */
	protected static function store_photo($photo_id, $blog_id, $child_id, $slot)
	{
		$bytes = static::download_photo($photo_id);
		$extension = $bytes === null ? 'png' : 'jpg';
		if ($bytes === null)
		{
			$bytes = static::fallback_png($photo_id);
		}

		$public = is_dir(DOCROOT.'public') ? DOCROOT.'public'.DIRECTORY_SEPARATOR : DOCROOT;
		$directory = $public.'uploads/blogs/'.$blog_id.'/'.$child_id;
		if ( ! is_dir($directory) && ! mkdir($directory, 0775, true))
		{
			throw new \RuntimeException('Could not create the blog image directory.');
		}

		$relative = 'uploads/blogs/'.$blog_id.'/'.$child_id.'/'.$slot.'.'.$extension;
		if (file_put_contents($public.$relative, $bytes) === false)
		{
			throw new \RuntimeException('Could not store the blog image.');
		}

		return $relative;
	}

	/**
	 * @param int $photo_id Picsum photo id
	 * @return string|null JPEG bytes
	 */
	protected static function download_photo($photo_id)
	{
		$photo_id = (int) $photo_id;
		if (array_key_exists($photo_id, static::$photo_bytes))
		{
			return static::$photo_bytes[$photo_id];
		}

		$url = 'https://picsum.photos/id/'.$photo_id.'/1200/720.jpg';
		$handle = curl_init($url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 8);
		curl_setopt($handle, CURLOPT_TIMEOUT, 20);
		curl_setopt($handle, CURLOPT_USERAGENT, 'todo-list-seed/1.0');
		$body = curl_exec($handle);
		$status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);

		if ($status !== 200 || ! is_string($body) || strncmp($body, "\xFF\xD8", 2) !== 0)
		{
			static::$photo_bytes[$photo_id] = null;

			return null;
		}

		static::$photo_bytes[$photo_id] = $body;

		return $body;
	}

	/**
	 * Wide placeholder used only when a photo cannot be downloaded.
	 *
	 * @param int $photo_id
	 * @return string
	 */
	protected static function fallback_png($photo_id)
	{
		$width = 320;
		$height = 180;
		$palettes = array(
			array(array(186, 214, 232), array(244, 214, 168)),
			array(array(30, 64, 92), array(232, 168, 76)),
			array(array(214, 228, 196), array(90, 122, 72)),
			array(array(48, 52, 64), array(176, 184, 196)),
		);
		$palette = $palettes[$photo_id % count($palettes)];
		$raw = '';

		for ($y = 0; $y < $height; $y++)
		{
			$blend = $y / ($height - 1);
			$red = (int) ($palette[0][0] + ($palette[1][0] - $palette[0][0]) * $blend);
			$green = (int) ($palette[0][1] + ($palette[1][1] - $palette[0][1]) * $blend);
			$blue = (int) ($palette[0][2] + ($palette[1][2] - $palette[0][2]) * $blend);
			$raw .= "\x00".str_repeat(chr($red).chr($green).chr($blue), $width);
		}

		$signature = "\x89PNG\r\n\x1a\n";
		$ihdr = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

		return $signature
			.static::png_chunk('IHDR', $ihdr)
			.static::png_chunk('IDAT', zlib_encode($raw, ZLIB_ENCODING_DEFLATE))
			.static::png_chunk('IEND', '');
	}

	/**
	 * @param string $type
	 * @param string $data
	 * @return string
	 */
	protected static function png_chunk($type, $data)
	{
		return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
	}

	/**
	 * Four articles. Extra seed counts repeat this set.
	 *
	 * @return array
	 */
	protected static function stories()
	{
		return array(
			array(
				'category' => 'Thời sự',
				'title' => 'Đường ven sông thông xe, người đi làm bớt nửa giờ mỗi buổi sáng',
				'children' => array(
					array(
						'heading' => 'Nhịp xe mới trên con đường chờ mười tám tháng',
						'blocks' => array(
							array('type' => 'p', 'text' => 'Sáng thứ Hai, tuyến đường ven sông dài 4,2 km chính thức thông xe sau mười tám tháng thi công. Từ 6 giờ, dòng xe máy và ô tô đã nối nhau theo hướng vào trung tâm. Nhiều người dừng lại vài giây ở đầu cầu chỉ để nhìn mặt đường mới, phẳng và còn thoảng mùi nhựa.'),
							array('type' => 'p', 'text' => 'Trước đây, quãng đường từ bến phà cũ vào khu văn phòng phải vòng qua ngã tư thường xuyên ùn tắc. Chị Lan, nhân viên một công ty logistics, nói chị phải xuất phát từ 6 giờ 15 để kịp cuộc họp 8 giờ. Sáng nay chị tới nơi lúc 7 giờ 40, còn dư thời gian mua cà phê ở quán đầu hẻm.'),
							array('type' => 'image', 'photo' => 1015),
							array('type' => 'p', 'text' => 'Mặt đường rộng 14 mét, gồm hai làn ô tô và một làn hỗn hợp sát hè. Dải phân cách được trồng cỏ và hàng cau nhỏ. Hệ thống đèn đã bật thử từ tuần trước. Công nhân còn hoàn thiện hai điểm chờ xe buýt và lối xuống bến sông, dự kiến bàn giao nốt trong tháng này.'),
							array('type' => 'quote', 'text' => 'Người dân không cần một cây cầu để chụp ảnh. Họ cần một lối đi mà buổi sáng không phải đứng giữa khói xe.'),
							array('type' => 'p', 'text' => 'Ban quản lý dự án cho biết phần lớn hạng mục chậm vì phải dịch chuyển đường ống nước cũ và chờ mặt bằng một đoạn 200 mét gần chợ. Khi mặt bằng được bàn giao, nhà thầu huy động thêm ca đêm để bù tiến độ. Kinh phí phát sinh được giữ trong phần dự phòng đã duyệt từ đầu năm.'),
							array('type' => 'image', 'photo' => 1036),
							array('type' => 'p', 'text' => 'Chính quyền phường sẽ theo dõi lưu lượng trong hai tuần đầu. Nếu xe tải nặng đi vào giờ tan tầm, biển cấm theo giờ sẽ được đặt lại. Người dân khu tập thể ven sông đề nghị thêm một cầu vượt bộ hành trước cổng trường. Đề xuất này được ghi nhận và đưa vào kế hoạch quý tới, chưa có ngày khởi công.'),
						),
					),
				),
			),
			array(
				'category' => 'Đời sống',
				'title' => 'Chợ sớm trong phố cũ vẫn mở từ lúc trời còn xám',
				'children' => array(
					array(
						'heading' => 'Ba giờ sáng, sạp rau đã có người cân',
						'blocks' => array(
							array('type' => 'p', 'text' => 'Trong phố cũ, chợ không chờ trời sáng. Từ 3 giờ, xe ba gác đã đổ rau, cá và hoa vào hai dãy lều tôn. Đèn dây vàng đủ để nhìn lá héo và mắt cá. Người bán nói nhỏ, người mua cũng nói nhỏ, như thể cả khu phố vẫn đang ngủ.'),
							array('type' => 'image', 'photo' => 1067),
							array('type' => 'p', 'text' => 'Bà Hòa giữ sạp rau ở góc trái đã mười hai năm. Bà lấy hàng từ một vườn ở ngoại thành, xe về lúc 2 giờ 30. Sáng nay rau muống non và cải ngọt chiếm nửa bàn. Bà buộc từng mớ bằng dây chuối, không dùng túi nilon với khách quen. "Họ mang làn đi, mình đỡ phải tính tiền túi", bà nói trong lúc rút tiền lẻ từ túi áo blu.'),
							array('type' => 'p', 'text' => 'Khách đầu tiên thường là chủ quán cơm và bếp ăn tập thể. Họ mua số lượng lớn, trả nhanh, rồi nhường chỗ cho người đi làm ghé sau 6 giờ. Giá buổi sớm nhỉnh hơn một chút so với trưa vì rau còn nguyên và chưa dập. Buổi trưa, phần còn lại được bán giảm cho người nội trợ đến muộn.'),
							array('type' => 'quote', 'text' => 'Chợ này sống được vì người ta vẫn muốn nhìn thấy thứ mình ăn, không chỉ đọc tên trên ứng dụng.'),
						),
					),
					array(
						'heading' => 'Một quán cà phê giữ chỗ ngồi cho người giao hàng',
						'blocks' => array(
							array('type' => 'p', 'text' => 'Ngay cổng chợ, quán cà phê của anh Khôi chỉ có tám ghế nhựa và một máy pha cũ. Anh mở cửa cùng lúc xe rau vào. Thực đơn không đổi: đen đá, bạc xỉu và bánh mì ốp la. Từ năm ngoái, anh dành hai ghế sát cửa cho người giao hàng resting giữa ca, không bắt họ gọi món nếu chỉ ngồi mười phút.'),
							array('type' => 'image', 'photo' => 201),
							array('type' => 'p', 'text' => 'Anh kể doanh thu không tăng vì chính sách đó, nhưng quán đỡ trống trải vào khoảng 4 giờ đến 5 giờ. Vài shipper để lại tiền nước lọc hoặc mua thêm một ổ bánh khi tan ca. Tường quán dán lịch thi đấu bóng đá và một tờ ghi số điện thoại thợ sửa xe máy trong hẻm.'),
							array('type' => 'p', 'text' => 'Chính quyền phường đang bàn chuyện sắp xếp lại các sạp lấn lòng đường. Người bán lo mất chỗ quen. Một cuộc họp được hẹn vào cuối tuần, có cả đại diện tiểu thương. Chưa ai nói đến việc dời chợ. Điều họ muốn nghe trước là giờ nào thì được dựng khung, và giờ nào thì phải thu vào cho xe cứu thương đi lọt.'),
						),
					),
				),
			),
			array(
				'category' => 'Công nghệ',
				'title' => 'Nhà máy linh kiện bật ca đêm để kịp lô hàng cuối tháng',
				'children' => array(
					array(
						'heading' => 'Dây chuyền không tắt đèn sau 22 giờ',
						'blocks' => array(
							array('type' => 'p', 'text' => 'Khu công nghiệp phía đông bật thêm một ca sản xuất từ tuần này. Nhà máy lắp ráp bo mạch nhận đơn hàng gấp của một đối tác trong nước, thời hạn giao là ngày 28. Thay vì thuê thêm người mới, ban giám đốc chia ca đêm cho công nhân đã đứng máy ít nhất sáu tháng, kèm phụ cấp và bữa ăn khuya.'),
							array('type' => 'p', 'text' => 'Trong xưởng, ánh đèn trắng đều, không có nhạc. Mỗi trạm có màn hình nhỏ hiện số lỗi trong mười phút gần nhất. Nếu vạch đỏ vượt ngưỡng, cả cụm dừng lại để kiểm tra mẻ linh kiện, không chờ hết ca. Một kỹ sư mới về nước nói quy trình này chậm hơn lúc mọi thứ chạy thông, nhưng đỡ phải thu hồi cả lô sau đó.'),
							array('type' => 'image', 'photo' => 1016),
							array('type' => 'quote', 'text' => 'Ca đêm không phải để chứng minh nhà máy chịu được áp lực. Ca đêm chỉ đáng mở khi người đứng máy vẫn còn đủ tỉnh để nhìn thấy lỗi.'),
							array('type' => 'p', 'text' => 'Phòng nhân sự giới hạn ca đêm ở bốn đêm mỗi tuần cho một người. Ai có con nhỏ được ưu tiên giữ ca ngày. Xe đưa đón xuất bến lúc 21 giờ ở ba điểm trong thành phố. Công nhân nói phần họ ngại nhất không phải giờ làm, mà là đoạn đường về lúc 6 giờ sáng, khi xe buýt công cộng còn thưa.'),
							array('type' => 'image', 'photo' => 325),
							array('type' => 'p', 'text' => 'Đơn hàng lần này gồm 40.000 bo mạch cho thiết bị đo trong nhà máy thực phẩm. Nếu giao đúng hẹn, đối tác ký tiếp hợp đồng sáu tháng. Ban giám đốc chưa công bố doanh thu. Họ chỉ xác nhận ca đêm sẽ dừng ngay khi lô hàng cuối rời kho, không giữ thành lịch thường xuyên.'),
						),
					),
				),
			),
			array(
				'category' => 'Thể thao',
				'title' => 'Đội chủ nhà giữ mạch bất bại sau trận mưa trên sân nhà',
				'children' => array(
					array(
						'heading' => 'Bàn thắng đến ở phút bù giờ thứ ba',
						'blocks' => array(
							array('type' => 'p', 'text' => 'Mưa bắt đầu từ giữa hiệp một và không ngớt đến hết trận. Mặt cỏ trơn, bóng đi chậm, các pha phối hợp ngắn liên tục bị cắt. Khán đài vẫn kín. Người xem mặc áo mưa nilon vàng, cùng màu với dải băng cổ vũ phát từ đầu mùa.'),
							array('type' => 'image', 'photo' => 429),
							array('type' => 'p', 'text' => 'Đội khách nhập cuộc chắc chắn. Họ lùi thấp, chờ những quả phạt góc và những đường chuyền dài cho tiền đạo trung tâm. Đội chủ nhà giữ bóng nhiều hơn nhưng thiếu một đường chuyền cuối đủ sắc. Thủ môn khách có hai tình huống cản phá gần trong vòng cấm ở các phút 61 và 74.'),
							array('type' => 'p', 'text' => 'Bàn thắng đến ở phút bù giờ thứ ba. Tiền vệ cánh đi bóng sát đường biên, căng ngang tầm thấp. Cầu thủ vào thay người ở phút 80 đệm một chạm, bóng chạm cột dọc rồi nằm trong lưới. Trọng tài không cần xem lại. Khán đài đứng dậy muộn nửa nhịp vì mưa làm tiếng reo bị nuốt.'),
							array('type' => 'quote', 'text' => 'Chúng tôi không chơi đẹp hôm nay. Chúng tôi chơi đúng với mặt sân mà mình có.'),
						),
					),
					array(
						'heading' => 'Huấn luyện viên chưa muốn nói về ngôi đầu',
						'blocks' => array(
							array('type' => 'p', 'text' => 'Sau trận, huấn luyện viên từ chối xếp đội mình vào nhóm tranh vô địch. Ông nhắc lịch thi đấu dày trong mười hai ngày tới: một trận sân khách và một trận cup. Đội đang có tám trận liên tiếp không thua ở giải quốc nội, nhưng hiệu số bàn thắng không còn thoải mái như đầu mùa.'),
							array('type' => 'image', 'photo' => 488),
							array('type' => 'p', 'text' => 'Phòng y tế ghi nhận hai cầu thủ bị chuột rút và một người đau mắt cá. Không ai phải rời sân bằng cáng. Buổi tập ngày mai được rút còn một tiếng, chủ yếu là hồi phục và xem lại những tình huống mất bóng ở giữa sân. Cầu thủ trẻ ghi bàn ở phút cuối sẽ được giữ trong đội hình xuất phát trận tới nếu kết quả kiểm tra vào sáng mai ổn.'),
							array('type' => 'p', 'text' => 'Ban tổ chức đánh giá mặt sân vẫn đạt yêu cầu dù mưa lớn. Hệ thống thoát nước làm năm ngoái đã phát huy tác dụng: không có vũng nước đọng trong vòng cấm sau giờ giải lao. Người hâm mộ chỉ đề nghị mái che thêm ở khán đài C, nơi một phần ghế vẫn trống vì nước tạt vào. Đề nghị này nằm ngoài thẩm quyền của câu lạc bộ.'),
						),
					),
				),
			),
		);
	}
}
