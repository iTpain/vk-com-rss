<?php

error_reporting(0);

class Monica {
	
	private $urls;
	private $rows;
	private $output = '';
	private $header  = array(
		"Accept-Encoding"=>"gzip,deflate,sdch",
		"Accept-Language"=>"ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4",
		"Accept"=>"text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
		"User-Agent"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.143 Safari/537.36",
		"Host"=>"api.vkontakte.ru",
		"Connection"=>"keep-alive");
	
	function Monica($rows = 10) {
		$this->rows = $rows > 200 ? 200 : $rows;
	}

	function vkgroup($group,$token,$captcha_sid,$captcha_key) {
		if($captcha_sid > NULL) {$captcha = '&captcha_sid=' . $captcha_sid . '&captcha_key=' . $captcha_key . '';}
		$this->urls[] = 'https://api.vkontakte.ru/method/wall.get?domain=' . $group . '&filter=all&count=' . $this->rows . '&access_token=' . $token . $captcha;
	}
	
	function build() {
		foreach ($this->urls as $url) {
			$data = $this->Curl($url);
			if($data->error->error_msg == 'Captcha needed') {
				print_r($this->captcha_template($data->error->captcha_sid,$data->error->captcha_img));
			}
			$group_name = $this->GroupName($url);
			$group_info = $this->Curl('https://api.vkontakte.ru/method/groups.getById?group_id=' . $group_name . '&fields=description');
			if($group_info->error->error_code == 100) break;
			foreach ($data->response as $post_key => $post) {
				if($post->id == NULL) continue;
				if($post->date == 1408463992) break;			// My little hack
				$this->item .= "<item>";
				if($post_key == 2 && $post->is_pinned == NULL) {$group_last_update_data = date('r', $post->date);}
				$this->attachment = NULL;
				foreach ($post->attachments as $attachments_key => $attachment) {
					# Photo attachment
					if($attachment->type == 'photo') {
						$this->attachment .= '<p><img src="http://liamka.me/lab/rss_groups_vk/i/camera.png"> <img src="' . $attachment->photo->src_big . '">';
					}
					# Audio attachment
					if($attachment->type == 'audio') {
						$this->attachment .= '<p><img src="http://liamka.me/lab/rss_groups_vk/i/audio.png"> <a href="http://vk.com/wall' . $post->to_id . '_' . $post->id . '">' . $attachment->audio->artist . ' - ' . $attachment->audio->title . '</a>';
					}
					# Video attachment
					if($attachment->type == 'video') {
						$this->attachment .= '<p><img src="http://liamka.me/lab/rss_groups_vk/i/video.png"> <a href="http://vk.com/wall' . $post->to_id . '_' . $post->id . '"><img src="' . $attachment->video->image . '">' . $attachment->video->title . ' - ' . $attachment->video->description . '</a>';
					}
					# Poll attachment
					if($attachment->type == 'poll') {
						$this->attachment .= '<p><img src="http://liamka.me/lab/rss_groups_vk/i/poll.png"> <a href="http://vk.com/wall' . $post->to_id . '_' . $post->id . '">' . $attachment->poll->question . '</a>';
					}
					# Doc attachment
					if($attachment->type == 'doc') {
						$this->attachment .= '<p><img src="http://liamka.me/lab/rss_groups_vk/i/doc.png"> <a href="' . $attachment->doc->url . '"><img src="' . $attachment->doc->thumb . '"></a>';
					}
				}
				if($post->post_type == 'Copy') {$post_type = 'Repost';} else {$post_type = 'Post';}
				$this->item .= '<title>' . ucfirst($post_type) . '</title>';
				$this->item .= '<link>http://vk.com/wall' . $post->to_id . '_' . $post->id . '</link>';
				$this->item .= '<description><![CDATA[' . $post->text . ' ' . $this->attachment . ']]></description>';
				$this->item .= '<pubDate>' . date('r', $post->date) . '</pubDate>';
				$this->item .= '<guid>http://vk.com/wall' . $post->to_id . '_' . $post->id . '</guid>';
				$this->item .= "</item>\n";
			}
			if($group_info->response[0]->name > NULL) {$group_name = $group_info->response[0]->name;}
			$this->output .= '<title>' . $group_name . '</title>';
			$this->output .= '<link>http://vk.com/' . $group_name . '</link>';
			$this->output .= '<description><![CDATA[' . $group_info->response[0]->description . ']]></description>';
			$this->output .= '<lastBuildDate>' . $group_last_update_data . '</lastBuildDate>';
			$this->output .= '<language>ru-RU</language>';
			$this->output .= '<generator>http://liamka.me/</generator>';
			$this->output .= $this->item;

		}

		echo $this->output;
	}

	function captcha_template($captcha_sid,$captcha_img) {
		return '<form method="post"><img src="'.$captcha_img.'"><input type="text" name="captcha_key" value=""><input type="hidden" name="captcha_sid" value="'.$captcha_sid.'"><p><input type="submit" name="submit_captcha" value="RUN"></form>';
	}

	function Curl($url) {
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		return json_decode($result);
	}

	function GroupName($url) {
		$dsfsdf = parse_url($url);
		$vars = explode('&', $dsfsdf['query']);
		foreach ($vars as $var) {
			$t = explode('=', $var);
			return $group[$t[0]] = $t[1];
			break;
		}
	}
}

?>