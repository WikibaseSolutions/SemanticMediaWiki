<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\Tokenizer\JaTinySegmenterTokenizer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\Tokenizer\JaTinySegmenterTokenizer
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class JaTinySegmenterTokenizerTest extends TestCase {

	public function testCanConstruct() {
		$tokenizer = $this->getMockBuilder( '\Onoi\Tesa\Tokenizer\Tokenizer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\Onoi\Tesa\Tokenizer\JaTinySegmenterTokenizer',
			new JaTinySegmenterTokenizer( $tokenizer )
		);
	}

	/**
	 * @dataProvider stringProvider
	 */
	public function testTokenize( $string, $expected ) {
		$instance = new JaTinySegmenterTokenizer();

		$this->assertEquals(
			$expected,
			$instance->tokenize( $string )
		);
	}

	public function stringProvider() {
		$provider[] = [
			'極めてコンパクトな日本語分かち書きソフトウェアです。',
			[
				'極め', // should be 極めて
				'て',
				'コンパクト',
				'な',
				'日本',
				'語分',
				'かち',
				'書き',
				'ソフトウェア',
				'です',
				'。'
			]
		];

		$provider[] = [
			'日本語の新聞記事であれば文字単位で95%程度の精度で分かち書きが行えます。 ',
			[
				'日本語',
				'の',
				'新聞',
				'記事',
				'で',
				'あれ',
				'ば',
				'文字',
				'単位',
				'で',
				'9',
				'5',
				'%',
				'程度',
				'の',
				'精度',
				'で',
				'分かち',
				'書き',
				'が',
				'行え',
				'ます',
				'。'

			]
		];

		$provider[] = [
			'私の名前は中野です',
			[
				'私',
				'の',
				'名前',
				'は',
				'中野',
				'です'
			]
		];

		$provider[] = [
			'TinySegmenterは25kBで書かれています。',
			[
				'TinySegmenter',
				'は',
				'2',
				'5',
				'kB',
				'で',
				'書か',
				'れ',
				'て',
				'い',
				'ます',
				'。'
			]
		];

		$provider[] = [
			'隣の客はAK47振りかざしてギャアギャアわめきたてる客だ。',
			[
				'隣',
				'の',
				'客',
				'は',
				'AK',
				'4',
				'7',
				'振り',
				'かざ', // should be かざし
				'し',
				'て',
				'ギャアギャア',
				'わめき',
				'た',
				'てる',
				'客',
				'だ',
				'。'
			]
		];

		// See JaCompoundGroupTokenizerTest for comparison
		$provider[] = [
			'と歓声を上げていました。 十勝農業改良普及センターによりますと',
			[
				'と',
				'歓声',
				'を',
				'上げ',
				'て',
				'い',
				'まし',
				'た',
				'。',
				'十勝農業',
				'改良',
				'普及',
				'センター',
				'により',
				'ます',
				'と'
			]
		];

		// See IcuWordBoundaryTokenizerTest
		$provider[] = [
			"公明執ようなＳＮＳもストーカー行為の対象に",
			[
				'公明執',
				'よう',
				'な',
				'ＳＮＳ',
				'も',
				'ストーカー',
				'行為',
				'の',
				'対象',
				'に'
			]
		];

		// https://github.com/chezou/TinySegmenter.jl/blob/master/test/timemachineu8j.txt

		return $provider;
	}

}
