<?php

// Получение доступа к header или text идёт через nextSibling
class Parser
{
    // Ссылка, которую будем парсить
    private static $url = 'https://habr.com/ru/all/';
    
    public function write_html()
    {
        $content = $this->get_content();
        $file = fopen('html.html', 'w');
        fwrite($file, $content);
    }

    // Публичный интерфейс - запуск программы
    public function run()
    {
        $this->create_DOM();
        $array_of_divs = $this->get_all_divs();
        $this->get_arrays($array_of_divs);  // Получение массивов с логинами, заголовками и текстами
        $this->count_of_articles = count($this->array_of_logins);
        $this->create_xml();
    }

    private function get_content()
    {
        $html = file_get_contents(self::$url);
        return $html;
    }

    // Получить корневой DOM-объект
    private function create_DOM()
    {
        $get_content = $this->get_content();
        $this->dom = new DOMDocument();
        $this->dom->loadHTML($get_content);
    }

    // Получить список всех тегов <div>, из которых достаются login, header и text
    private function get_all_divs()
    {
        $articles = $this->dom->getElementsByTagName('article');  // Список всех тегов <article>
        $array_of_divs = array();
        foreach ($articles as $article) {
            $array_of_divs[] = $article->firstElementChild;  // Список всех тегов <div>
        }
        return $array_of_divs;
    }

    private function get_login($parent_tag)
    {
        $div_1 = $parent_tag->firstElementChild;
        $div_2 = $div_1->firstElementChild;
        $login = $div_2->firstElementChild;
        return $login->textContent;
    }

    // Получаем массив с логинами
    private function get_array_of_logins($array_of_divs)
    {
        $this->array_of_logins = array();
        foreach ($array_of_divs as $div) {
            $this->array_of_logins[] = $this->get_login($div);
        }
    }

    private function get_header($parent_tag)
    {
        $div_0 = $parent_tag->firstElementChild;
        $h1 = $div_0->nextElementSibling;
        return $h1->textContent;
    }

    // Получаем массив с заголовками
    private function get_array_of_headers($array_of_divs)
    {
        $this->array_of_headers = array();
        foreach ($array_of_divs as $div) {
            $this->array_of_headers[] = $this->get_header($div);
        }
    }

    // Поскольку теги <div> могут иметь разную структуру, необходимо обрабатывать наличие сниппетов
    private function get_tag_with_text($parent_tag)
    {
        if ($parent_tag->childElementCount == 4) {
            $div_0 = $parent_tag->firstElementChild;
            $div_1 = $div_0->nextElementSibling;
            $div_2 = $div_1->nextElementSibling;
            $div_3 = $div_2->nextElementSibling;
            return $div_3;
        } elseif ($parent_tag->childElementCount == 5) {
            $div_0 = $parent_tag->firstElementChild;
            $div_1 = $div_0->nextElementSibling;
            $div_2 = $div_1->nextElementSibling;
            $div_3 = $div_2->nextElementSibling;
            $div_4 = $div_3->nextElementSibling;
            return $div_4;
        }
    }

    // Получаем массив с текстами
    private function get_array_of_texts($array_of_divs)
    {
        $this->array_of_texts = array();
        foreach ($array_of_divs as $div) {
            $div_with_text = $this->get_tag_with_text($div);
            $tag = $div_with_text->firstElementChild;
            if ($tag->textContent != "") {
                $this->array_of_texts[] = $tag->textContent;
            } else {
                $this->array_of_texts[] = $tag->nextElementSibling->textContent;
            }
        }
    }

    // Сохраняем три массива в поля экземпляра
    private function get_arrays($array_of_divs)
    {
        $this->get_array_of_logins($array_of_divs);
        $this->get_array_of_headers($array_of_divs);
        $this->get_array_of_texts($array_of_divs);
    }

    // Создание выходного XML-файла
    private function create_XML()
    {
        $output_xml = new DOMDocument("1.0", "utf-8");
        $root = $output_xml->createElement("root");
        $output_xml->appendChild($root);
        for ($i = 0; $i < $this->count_of_articles; $i++) {
            $article = $output_xml->createElement("article");
            $login = $output_xml->createElement("login", $this->array_of_logins[$i]);
            $header = $output_xml->createElement("header", $this->array_of_headers[$i]);
            $text = $output_xml->createElement("text", $this->array_of_texts[$i]);
            $article->appendChild($login);
            $article->appendChild($header);
            $article->appendChild($text);
            $root->appendChild($article);
        }
        $this->write_xml($output_xml);
    }

    private function write_xml($xml)
    {
        $time = time();
        $day = date("d", $time);
        $month = date("m", $time);
        $year = date("Y", $time);
        $date = $day . "_" . $month . "_" . $year;
        $name = "Habr_" . $date . ".xml";
        $xml->save($name);
    }
}

// Запуск скрипта
$parser = new Parser;
$parser->run();