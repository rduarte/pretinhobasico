<?php
ini_set('default_charset', 'utf-8');
header('Content-type: application/xml');

$pathinfo = pathinfo(__FILE__);
require_once($pathinfo['dirname'].'/spyc.php');

// Buscar pelos programas no site da Atlântida
$html = utf8_encode(file_get_contents("http://www.clicrbs.com.br/atlantidafm/jsp/default.jsp?uf=1&local=1&template=3343.dwt&pSection=726&section=12"));
preg_match_all(("/» &nbsp;Pretinho das (.*)<!-- Escutar--><!-- Baixar-->&nbsp;|&nbsp;<a/"), ($html), $matches);
$matches = explode("»", $matches[0][0]);

foreach ($matches as $match){
  // Pegando titulos
  preg_match("/&nbsp;(.*):&nbsp;</",$match, $matches);
  if (!empty($matches[1])) $titulos[] = $matches[1];
  // Pegando arquivos
  preg_match("/<a onclick=\"(.*)\" href=\"(.*)\">Escutar/",$match, $matches);
  if (!empty($matches[1])) $arquivos[] = $matches[2];
}

// Criar array unificado
$data = "0";
foreach($titulos as $id => $titulo){

  if((substr($titulo,0,8) == 'Pretinho')){

    // Cache com informações sobre o arquivo
    $info = pathinfo($arquivos[$id]);
    if(file_exists('./yml/'.$info['filename'].'.yml')){
      $programa = Spyc::YAMLLoad('./yml/'.$info['filename'].'.yml');
    }else{
      $headers = get_headers($arquivos[$id], 1);
      $programa = array('titulo' => $titulo, 'arquivo' => $arquivos[$id], 'headers' => $headers);
      file_put_contents('./yml/'.$info['filename'].'.yml', Spyc::YAMLDump($programa));
    }
    $programas[] = $programa;

    // Busca a data do programa mais atual
    if(strtotime($programa['headers']['Last-Modified']) > $data){
      $data = strtotime($programa['headers']['Last-Modified']);
      $last_modified = $programa['headers']['Last-Modified'];
    }
  }
}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Pretinho Básico - Rádio Atlântida</title>
        <description>Na Rádio Atlântida, de segunda a sábado às 13h e às 18h. E aqui, a qualquer hora do dia ou da noite.</description>
        <link>http://wp.clicrbs.com.br/pretinhobasico/</link>
        <lastBuildDate><?php echo $last_modified; ?></lastBuildDate>
        <pubDate><?php echo $last_modified; ?></pubDate>
        <copyright>Copyright&#xA9; <?php echo date("Y"); ?> - Grupo RBS</copyright>
        <language>pt-br</language>
        <generator>Ricardo Antonio Duarte - ricardo@ricardoduarte.com</generator>
        <atom:link href="http://ricardoduarte.com/feeds/pretinhobasico/" rel="self" type="application/rss+xml" />
<?php
foreach($programas as $programa){
?>
        <item>
            <title><?php echo $programa['titulo']; ?></title>
            <description><?php echo $programa['titulo']; ?></description>
            <link><?php echo "http://wp.clicrbs.com.br/pretinhobasico/".date("Y/m/d/",strtotime($programa['headers']['Last-Modified'])); ?></link>
            <enclosure url="<?php echo $programa['arquivo']; ?>" length="<?php echo $programa['headers']['Content-Length']; ?>" type="<?php echo $programa['headers']['Content-Type']; ?>"/>
            <guid isPermaLink="true"><?php echo $programa['arquivo']; ?></guid>
            <pubDate><?php echo $programa['headers']['Last-Modified']; ?></pubDate>
        </item>
<?php
}
?>
    </channel>
</rss>

