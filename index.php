<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title></title>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  
<script> 
$(document).ready( function () {
  var start_date = $( '#startInp' );
  var finish_date = $( '#finishInp' );
  start_date.datepicker({
    numberOfMonths: 1,
    selectOtherMonths: true,
    firstDay: 1,
    minDate: '-1m',
    maxDate:  0,
    dateFormat: 'dd.mm.yy',
    onSelect:	function( dateText, inst ) {
        finish_date.datepicker('option', 'minDate', dateText);
        if(finish_date.val() != '' ){$('input[type="submit"]').prop("disabled", false);}
    },
    closeText: 'Ok'
  });
    
  finish_date.datepicker({
    numberOfMonths: 1,
    selectOtherMonths: true,
    firstDay: 1,
    minDate: '-1m',
    maxDate:  0,
    dateFormat: 'dd.mm.yy',
    onSelect:	function( dateText, inst ) {
        start_date.datepicker('option', 'maxDate', dateText);
        if(start_date.val() != '' ){$('input[type="submit"]').prop("disabled", false);}
    },
    closeText: 'Ok'
  });
  $('input[type="submit"]').on("click", async function(){
    let exchanges = await $.get('https://betaservicescbr-default-rtdb.firebaseio.com/currency.json');
    if( exchanges ) {
      $('#exchanges tbody tr').remove();
      let st = new Date( start_date.val().substr( 6, 4 ), start_date.val().substr( 3, 2 )-1, start_date.val().substr( 0, 2 ), 0, 0, );
      let fs = new Date( finish_date.val().substr( 6, 4 ), finish_date.val().substr( 3, 2 )-1, finish_date.val().substr( 0, 2 ), 0, 0, );
      for(let i=10;i<40;i++){
        let objEx = Object.values(exchanges)[0]['exchange']['point'+i];
        if(objEx){
          let day = new Date( objEx['date'].substr( 6, 4 ), objEx['date'].substr( 3, 2 )-1, objEx['date'].substr( 0, 2 ), 0, 0, );
           if(day.getTime() >= st.getTime() && day.getTime() <= fs.getTime()){
             $('#exchanges tbody').append('<tr><td>'+objEx['date']+'</td><td>'+objEx['value']+'</td></tr>');
           }
        }
      }
    }
  })
}); 
</script>
<style>#pickdate, #exchanges{width: 500px; text-align: center;} input{text-align: center;}</style>

</head>

<body result="<?=$result?>">
<?php
  $endDateBase = date("d/m/Y");
  $valuteCode = 'R01720';
  $ch = file_get_contents('https://betaservicescbr-default-rtdb.firebaseio.com/currency.json');
  
  

  
  if($ch == 'null'){
    $chn="Создаём базу";
    
    $startDateBase = date('d/m/Y',strtotime('-1 Month'));
    $xml = simplexml_load_string(file_get_contents('http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1='.$startDateBase.'&date_req2='.$endDateBase.'&VAL_NM_RQ='.$valuteCode), "SimpleXMLElement", LIBXML_NOCDATA);
    $array = json_decode(json_encode($xml),TRUE);

    $xmlParams =  simplexml_load_string(file_get_contents('http://www.cbr.ru/scripts/XML_daily.asp?date_req='.$startDateBase.'&VAL_NM_RQ='.$valuteCode), "SimpleXMLElement", LIBXML_NOCDATA);
    $arrayParams = json_decode(json_encode($xmlParams),TRUE);
  
    $numCode = '';
    $charCode ='';
    $name ='';
    $valuiID = '';
    foreach($arrayParams['Valute'] as $key => $itemParams) {
      if($itemParams['@attributes']['ID'] === $valuteCode){
        $valuiID =  $itemParams['@attributes']['ID'];
        $numCode = $itemParams['NumCode'];
        $charCode = $itemParams['CharCode'];
        $name = $itemParams['Name'];
      }
    }
  
    $toDB = '';
    $exchange = '';
    $i = 10;
    foreach($array['Record'] as $key => $item) {
      $value = $item['Value'];
      $date = $item['@attributes']['Date'];
      $exchange .= '"point'.$i++.'": {"date": "'.$date.'", "value": "'.$value.'"},';
    }
  
    $toDB = '{"valuteID": "'.$valuiID.'", "numCode": "'.$numCode.'", "сharCode" : "'.$charCode.'", "name" : "'.$name.'", "dateUpdate": "'.$endDateBase.'", "dateUpdate": "'.$endDateBase.'","exchange": {'.substr($exchange,0,-1).'} }';
    
    $ch = curl_init('https://betaservicescbr-default-rtdb.firebaseio.com/currency.json');
    curl_setopt($ch, CURLOPT_POST, true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS,$toDB);
    curl_exec($ch);
    curl_close($ch);
    header("refresh: 3;"); ?>
    <h1><?php print_r($chn) ?></h1>
<?php    
  }else{
  
    $chn="База уже есть.";
    $arrayShow = [];
    $chd = json_decode( $ch, true);
    foreach($chd as $key => $valuta ) {
      if($valuta["valuteID"] == $valuteCode && $valuta["dateUpdate"] == $endDateBase){
        $valActual = 'База свежая';
      } else {
        $valActual = 'Но база не свежая';
      }
      $arrayShow = $valuta['exchange'];
    }
  
?>
<h1><?php print_r($chn) ?></h1>
<table id="exchanges">    
    <thead><tr><th width="50%">Дата</th><th width="50%">Курс за 100 рублей</th></tr></thead>
    <tbody>
      <?php
        foreach($arrayShow as $key => $item) {
      ?><?$item?>
            <tr><td><?=$item['date']; ?></td><td><?=$item['value']?></td></tr>
      <?php
        }
        
      ?>
    </tbody>
</table>

<table id="pickdate">    
    <tbody>
      <tr><td><input id="startInp" type="text" placeholder="Укажите старт периода" /></td><td><input id="finishInp" type="text" placeholder="Укажите конец периода" /></td></tr>
      <tr><td colspan="2"><input type="submit" value="Показать" disabled/></td></tr>
    </tbody>
</table>
<?php } ?></br>

<?php  print_r($startDateBase) ?></br>
<?php  print_r($endDateBase) ?></br>
<?php print_r($valActual) ?>

</body>
</html>
