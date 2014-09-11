<?php

/**
 * FontColorByImage
 * Get contrast font color based on image
 *
 * @author		Michal Katuscak (michal@katuscak.cz)
 * @licence		Creative Commons BY-SA 3.0
 */

namespace Katuscak;

class FontColorByImage {

	private $imageUrl,
		$imageWidth,
		$imageHeight,
		$image,
		$tolerance,
		$palette;

	public function __construct($imageUrl,$tolerance=20) {
		$this->imageUrl = $imageUrl;
		$this->tolerance = $tolerance;
		$this->image = $this->getImage($this->imageUrl);
		list($this->imageWidth,$this->imageHeight) = $this->getWidthAndHeight($this->image);
	}

	public function get() {
		$dominanceColor = $this->getDominanceColor($this->image, $this->imageWidth, $this->imageHeight, $this->tolerance);
		$fontColor = $this->getFontColor($dominanceColor);
		if (is_array($this->palette)) {
			$fontColor = $this->getFontColorByPallete($fontColor,$this->palette);
		}
		return $fontColor;
	}

	public function setTolerance($tolerance) {
		$this->tolerance = $tolerance;
	}

	public function setPalette($palette) {
		$this->palette = $palette;
	}

	private function getImage ($imageUrl) {
		if (!file_get_contents($imageUrl)) {
			throw new \Exception("Image not found");
		}
		$ext = strtolower(array_pop(explode('.',$imageUrl)));
		if ($ext == "jpeg" || $ext == "jpg") {
			return ImageCreateFromJpeg($imageUrl);
		} elseif ($ext == "gif") {
			return ImageCreateFromGif($imageUrl);
		} elseif ($ext == "png") {
			return ImageCreateFromPng($imageUrl);
		} else {
			throw new \Exception("Image not supported");
		}
	}

	private function getWidthAndHeight($image) {
		$width = imagesx($image);
		$height = imagesy($image);
		return array($width,$height);
	}

	private function getColors($image,$width,$height) {
		$colors = array();
		for ($i=0; $i<$width; $i++) {
			for ($j=0; $j<$height; $j++) {
				$rgb = ImageColorAt($image, $i, $j);

				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;

				$colors[serialize(array($r,$g,$b))]++;
			}
		}
		$colors = $this->sortColors($colors);
		return $colors;
	}

	private function sortColors($colors) {
		$id_list = array_keys($colors);
		$score_list = array_values($colors);
		array_multisort($score_list,SORT_DESC,$id_list,SORT_DESC);
		$colors = array_combine($id_list,$score_list);
		return $colors;
	}

	private function getSimilarColors($colors,$tolerance) {
		$count = array();
		foreach ($colors as $color => $n) {
			$color = unserialize($color);
			foreach ($count as $k=>$c) {
				if (
					$c["color"]["r"] > $color[0]-$tolerance && $c["color"]["r"] < $color[0]+$tolerance
					&&
					$c["color"]["g"] > $color[1]-$tolerance && $c["color"]["g"] < $color[1]+$tolerance
					&&
					$c["color"]["b"] > $color[2]-$tolerance && $c["color"]["b"] < $color[2]+$tolerance
				) {
					$count[$k]["count"]["r"] += $n*$color[0];
					$count[$k]["count"]["g"] += $n*$color[1];
					$count[$k]["count"]["b"] += $n*$color[2];
					$count[$k]["num"] += $n;
					continue 2;
				}
			}
			$count[] = array (
				"color" => array(
					"r" => $color[0],
					"g" => $color[1],
					"b" => $color[2]
				),
				"count" => array(
					"r" => $n*$color[0],
					"g" => $n*$color[1],
					"b" => $n*$color[2]
				),
				"num" => $n
			);

		}
		$count = $this->sortSimilarColors($count);
		return $count;
	}

	private function sortSimilarColors($colors) {
		$new_count = array();
		foreach ($colors as $tmp) {
			$max_count = array();
			foreach ($colors as $k=>$c) {
				if ($c["num"] > $max_count["num"]) {
					$max_count = $c;
					$delete = $k;
				}
			}
			unset($colors[$delete]);
			$new_count[] = $max_count;
		}
		return $new_count;
	}

	private function getDominanceColor($image, $width, $height, $tolerance) {
		$colors = $this->getColors($image,$width,$height);
		$colors = $this->getSimilarColors($colors,$tolerance);
		foreach ($colors as $tmp) {
			return array(
				"r" => round($tmp["count"]["r"]/$tmp["num"]),
				"g" => round($tmp["count"]["g"]/$tmp["num"]),
				"b" => round($tmp["count"]["b"]/$tmp["num"])
			);
		}
	}

	private function getFontColor($dominanceColor) {
		$fontColor = $dominanceColor;
		$fontColor["r"] -= 128;
		$fontColor["g"] -= 128;
		$fontColor["b"] -= 128;
		$countSmaller = 0;
		if ($fontColor["r"]<0) $countSmaller++;
		if ($fontColor["g"]<0) $countSmaller++;
		if ($fontColor["b"]<0) $countSmaller++;
		if ($countSmaller == 2) {
			if ($fontColor["r"]>0) $fontColor["r"] = 255;
			if ($fontColor["g"]>0) $fontColor["g"] = 255;
			if ($fontColor["b"]>0) $fontColor["b"] = 255;
		} elseif ($countSmaller == 1) {
			if ($fontColor["r"]<0) $fontColor["r"] = 0;
			if ($fontColor["g"]<0) $fontColor["g"] = 0;
			if ($fontColor["b"]<0) $fontColor["b"] = 0;
		}
		if ($countSmaller == 3 || $countSmaller == 2) {
			if ($fontColor["r"]<0) $fontColor["r"] = 255+$fontColor["r"];
			if ($fontColor["g"]<0) $fontColor["g"] = 255+$fontColor["g"];
			if ($fontColor["b"]<0) $fontColor["b"] = 255+$fontColor["b"];
		}
		$fontColor["r"] = round($fontColor["r"]);
		$fontColor["g"] = round($fontColor["g"]);
		$fontColor["b"] = round($fontColor["b"]);
		return $fontColor;
	}

	private function getFontColorByPallete($fontColor,$palette) {
		$min_diff = array("diff"=>"256");
		foreach ($palette as $p) {
			$r1 = abs($fontColor["r"]-$p[0]);
			$r2 = abs($fontColor["g"]-$p[1]);
			$r3 = abs($fontColor["b"]-$p[2]);
			$r = $r1 + $r2 + $r3;
			if ($min_diff["diff"] > $r) {
				$min_diff["diff"] = $r;
				$min_diff["color"] = $p;
			}
		}
		$fontColor = array(
			"r" => $min_diff["color"][0],
			"g" => $min_diff["color"][1],
			"b" => $min_diff["color"][2]
		);
		return $fontColor;
	}

}