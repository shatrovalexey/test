<?php

/**
* Тестовое задание: "Разработать систему перевода имен графических файлов с кириллицы на латиницу.".
* 
* @author Шатров Алексей <mail@ashatrov.ru>
* @version 1.0
*
* В вызовах команд оболочки не везде используется quotemeta. Где не используется, там я посчитал,
* что нет необходимости. То есть, команда tmpnam, вряд ли, создаст имя с командой. Хотя, это возможно,
* но тогда задача усложняется и любой функции доверять не стоит и нужна проверка.
*
*/

function get_in_translate_to_en( $string ) {
	$arStrES = array('ае','уе','ое','ые','ие','эе','яе','юе','ёе','ее','ье','ъе','ый','ий');
	$arStrOS = array('аё','уё','оё','ыё','иё','эё','яё','юё','ёё','её','ьё','ъё','ый','ий');        
	$arStrRS = array('а$','у$','о$','ы$','и$','э$','я$','ю$','ё$','е$','ь$','ъ$','@','@');
	$replace = array(
		'А' => 'A','а' => 'a','Б' => 'B','б' => 'b','В' => 'V','в' => 'v','Г' => 'G','г' => 'g','Д' => 'D','д' => 'd',
		'Е' => 'Ye','е' => 'e','Ё' => 'Ye','ё' => 'e','Ж' => 'Zh','ж' => 'zh','З' => 'Z','з' => 'z','И' => 'I','и' => 'i',
		'Й' => 'Y','й' => 'y','К' => 'K','к' => 'k','Л' => 'L','л' => 'l','М' => 'M','м' => 'm','Н' => 'N','н' => 'n',
		'О' => 'O','о' => 'o','П' => 'P','п' => 'p','Р' => 'R','р' => 'r','С' => 'S','с' => 's','Т' => 'T','т' => 't',
		'У' => 'U','у' => 'u','Ф' => 'F','ф' => 'f','Х' => 'Kh','х' => 'kh','Ц' => 'Ts','ц' => 'ts','Ч' => 'Ch','ч' => 'ch',
		'Ш' => 'Sh','ш' => 'sh','Щ' => 'Shch','щ' => 'shch','Ъ' => '','ъ' => '','Ы' => 'Y','ы' => 'y','Ь' => '','ь' => '',
		'Э' => 'E','э' => 'e','Ю' => 'Yu','ю' => 'yu','Я' => 'Ya','я' => 'ya','@' => 'y','$' => 'ye'
	) ;
	
	$string = str_replace( $arStrES , $arStrRS , $string ) ;
	$string = str_replace( $arStrOS , $arStrRS , $string ) ;
	$string = preg_replace( '{\s+}' , '-' , $string ) ;

	return iconv( 'UTF-8' , 'UTF-8//IGNORE' , strtr( $string , $replace ) ) ;
}

/**
* @const MIME-тип файлов ZIP
*/

define( 'ZIP_MIME' , 'application/zip' ) ;

/**
* @const расширение файлов ZIP
*/

define( 'ZIP_EXT' , 'zip' ) ;

/**
* @const системная временная директория
*/
define( 'TMP_DIR' , '/tmp' ) ;

/**
* Удобная конструкция try-catch, служит для возможности выхода из блока
* с передачей сообщений в $exception->getMessage( ).
* Конструкция switch-case так же позволяет в нужный момент выйти из блока.
*/

try {
	/**
	* Обработка разных действий, если они заданы.
	*/
	switch ( @$_REQUEST[ 'action' ] ) {
		/**
		* Загрузка файла
		*/
		case 'upload' : {
			/**
			* Выход, если нет загруженных файлов.
			*/
			if ( empty( $_FILES ) ) {
				throw new Exception( 'Файл не указан' ) ;
			}

			/**
			* Выход, если нет файла из поля с именем "file".
			*/
			if ( empty( $_FILES[ 'file' ] ) ) {
				throw new Exception( 'Файл не указан' ) ;
			}

			/**
			* Выход, если нет файл загружен с ошибкой.
			*/
			if ( ! empty( $_FILES[ 'file' ][ 'error' ] ) ) {
				throw new Exception( 'Ошибка загрузки файла' ) ;
			}

			/**
			* @var string путь к загруженному файлу, по которому можно будет с ним работать.
			*/
			$local_file_path = tempnam( TMP_DIR , ZIP_EXT ) ;

			/**
			* Перемещение загруженного файла из папки загрузок PHP в папку, где с ним возможна работа.
			* Если в процессе перемещения произошла ошибка, то выход из блока.
			*/
			if ( ! move_uploaded_file( $_FILES[ 'file' ][ 'tmp_name' ] , $local_file_path ) ) {
				throw new Exception( 'Ошибка перемещения загруженного файла' ) ;
			}

			/**
			* @var string имя временной папки для разархивирования загруженного файла.
			*/

			do {
				$extracted_path = TMP_DIR . DIRECTORY_SEPARATOR . uniqid( ) ;
			} while ( is_dir( $extracted_path ) ) ;

			/**
			* Разархивирование загруженного файла в папку для разархивирования.
			* @var string вывод команды в STDOUT в виде строки.
			* @var int код завершения команды.
			*/

			exec(
				'LANG=ru_RU.UTF-8 && ' .
				"unzip -I utf8 '{$local_file_path}' -d '{$extracted_path}'" ,
				$result , $result_code
			) ;

			/**
			* Если разархивировать не удалось, то вывод информации из команды и выход из блока.
			*/
			if ( ! empty( $result_code ) ) {
				/**
				* Удаление загруженного файла.
				*/
				unlink( $local_file_path ) ;

				throw new Exception( "При разархивировании произошла ошибка:\n" . print_r( $result , true ) ) ;
			}

			/**
			* Удаление загруженного файла.
			*/
			unlink( $local_file_path ) ;

			/**
			* @var resource поиск графических файлов форматов jpg, gif, png в файлах папки архива.
			*/
			$fh_img = popen(
				"find '{$extracted_path}' -type 'f' -regextype 'awk' -iregex '^.*\\.(jpe?g|gif|png)\$'" ,
				'rb'
			) ;

			/**
			* Если произошла ошибка, то выход из блока.
			*/
			if ( empty( $fh_img ) ) {
				/**
				* Удаление разархивированных файлов и временной папки.
				*/
				exec( "rm -fr '{$extracted_path}'" ) ;

				throw new Exception( 'Файлы картинок не найдены' ) ;
			}

			/**
			* Чтение вывода поиска графических файлов.
			* @var string очередной найденный файл с петём к нему.
			*/
			while ( $file_path = fgets( $fh_img ) ) {
				/**
				* Удаление [\r\n]+
				*/
				$file_path = chop( $file_path ) ;

				/**
				* Функция pathinfo не поддерживает работу с многобайтовыми кодировками.
				* Поэтому придётся пользоваться рег.выром.
				* @var array переменные соответствия проверки рег.выра.
				*/
				if ( ! preg_match(
					'{^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$}u' ,
					$file_path ,
					$matches
				) ) {
					error_log( "Ошибка определения имени файла \"{$file_path}\"." ) ;
				}

				/**
				* @var string путь к найденному файлу.
				*/
				$file_dir = $matches[ 1 ] ;

				/**
				* @var string имя с расширением найденного файла.
				*/
				$file_name = $matches[ 2 ] ;

				/**
				* @var string имя с расширением найденного файла после транслита.
				*/
				$file_name_trans = get_in_translate_to_en( $file_name ) ;

				/**
				* Если имя найденного файла после транслита не изменилось, то пропуск его обработки.
				*/
				if ( $file_name_trans == $file_name ) {
					error_log( 'Файл "' . $file_path . '" обработки не требует' ) ;

					continue ;
				}

				/**
				* @var string имя нового файла с путём.
				*/
				$file_path_trans = $file_dir . PATH_SEPARATOR . $file_name_trans ;

				/**
				* @var int счётчик итераций.
				* Если новое имя файла совпадает с уже существующим именем ноды, то нужно изменить новое имя.
				*/
				for ( $i = 1 ; is_readable( $file_path_trans ) ; $i ++ ) {
					$file_path_trans = $file_dir . PATH_SEPARATOR . $i . '-' . $file_name_trans ;
				}

				/**
				* Изменение имени найденного файла.
				*/
				rename( $file_path , $file_path_trans ) ;

				/**
				* Рекурсивный поиск файлов HTML и HTM и замена в их содержимом старого имени файла на новое.
				* @todo Нет сопоставления путей для файлов.
				*/
				exec(
					"LANG=ru_RU.UTF-8 && " .
					"find '{$extracted_path}' -regextype 'awk' -type 'f' -iregex '^.*?\.html?$' -exec " .
					"sed -i 's/" . preg_quote( $file_name ) . "/" . preg_quote( $file_name_trans ) . "/g' {} +"
				) ;
			}

			/**
			* Закрытие вывода команды поиска графических файлов.
			*/
			fclose( $fh_img ) ;

			/**
			* @var string путь к новому архиву ZIP.
			*/
			$zip_path = tempnam( TMP_DIR , ZIP_EXT ) . '.' . ZIP_EXT ;


			/**
			* Создание нового архива ZIP и удаление файлов и папок.
			*/
			exec( "zip '{$zip_path}' -mr '{$extracted_path}'" , $result , $result_code ) ;

			/**
			* Выход из блока, если во время создания архива произошли ошибки.
			*/
			if ( ! empty( $result_code ) ) {
				throw new Exception( "Во время создания архива произошли ошибки:\n{$result}" ) ;
			}

			/**
			* Вывод нового архива как вложения для загрузки.
			*/
			header( 'Content-Type: ' . ZIP_MIME ) ;
			header( 'Content-Disposition: attachment;filename="Результат-' . date( 'Y-m-d-H-i-s' ) . '.' . ZIP_EXT . '"' ) ;

			readfile( $zip_path ) ;

			/**
			* Удаление файла ZIP.
			*/
			unlink( $zip_path ) ;

			/**
			* Конец обработки.
			*/
			exit( 0 ) ;
		}
	}
} catch ( Exception $exception ) {
	/**
	* Вывод ошибки.
	*/

	header( 'Content-Type: text/html; charset=utf-8' ) ;

?><!DOCTYPE>
<html>
	<head>
		<title>Ошибка во время выолнения</title>
		<meta charset="utf-8">
	</head>
	<body>
		<h1>Ошибка во время выолнения</h1>
		<xmp><?=htmlspecialchars( $exception->getMessage( ) )?></xmp>
		<p><a href=".">вернуться на страницу</a>.
	</body>
</html><?php
	exit( 0 ) ;
}

/**
* Вывод страницы для загрузки исходного ZIP-архива.
*/

header( 'Content-Type: text/html; charset=utf-8' ) ;

?><!DOCTYPE html>
<html>
	<head>
		<title>Преобразование ZIP-архива</title>
		<meta charset="utf-8">
	</head>
	<body>
		<h1>Преобразование ZIP-архива</h1>
		<form method="POST" enctype="multipart/form-data">
			<input type="hidden" name="action" value="upload">
			<label>
				<span>укажите архив ZIP:</span>
				<input type="file" name="file" accept="<?=ZIP_MIME?>" required>
			</label>
			<label>
				<span>обработать</span>
				<input type="submit" value="&rarr;">
			</label>
		</form>
	</body>
</html>