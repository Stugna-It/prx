<?php

    // file name = proxy type
    $files = [
        'socks5','socks4','http'
    ];

    $prx = [];

    foreach ($files as $f) {
        if (!is_file($f)) {
            continue;
        }
        $urls = file($f, FILE_IGNORE_NEW_LINES);
        foreach ($urls as $url) {
            $r = fromList($url,$f);
            $prx = array_merge($prx,$r);
            echo count($r) . " {$f} proxies from " . $url . PHP_EOL;
        }
    }

    $prx = array_unique($prx);

    $fp = fopen('proxy_all.txt', 'w');
    foreach ($prx as $proxy) {
        fwrite($fp, $proxy . PHP_EOL);
    }
    fclose($fp);
    echo count($prx) . " proxies save to proxy_all.txt". PHP_EOL;


    $prx = check($prx,1000);

    $fp = fopen('proxy.txt', 'w');
    foreach ($prx as $proxy) {
        fwrite($fp, $proxy . PHP_EOL);
    }
    fclose($fp);
    echo count($prx) . " working proxies save to proxy.txt". PHP_EOL;

    function check($prx,$threads,$timeout = 15) {
        $multi 	= curl_multi_init();
        $count = count($prx);
        $checked = 0;
        $found = 0;
        $ips 	= array_chunk($prx,$threads);
        $res = [];
        foreach($ips as $ip)     {
            for($i=0;$i<=count($ip)-1;$i++) {
                $curl[$i] = curl_init();
                curl_setopt($curl[$i],CURLOPT_RETURNTRANSFER,1);
                curl_setopt($curl[$i],CURLOPT_URL,"http://213.183.56.193/");
                curl_setopt($curl[$i],CURLOPT_PROXY,$ip[$i]);
                curl_setopt($curl[$i],CURLOPT_TIMEOUT,$timeout);
                curl_multi_add_handle($multi,$curl[$i]);
                $checked++;
            }

            do {
                curl_multi_exec($multi,$active);
                sleep(1);
            }   while   ( $active > 0 );

            foreach($curl as $cid => $cend)         {
                $con[$cid] = curl_multi_getcontent($cend);
                curl_multi_remove_handle($multi,$cend);
                if(strpos($con[$cid],'Hello !') > 0) {
                    $res[] = $ip[$cid];
                    echo "{$checked}/{$count} " . $ip[$cid] . PHP_EOL;
                    $found++;
                }
            }
            echo "{$checked}/{$count} found {$found}" . PHP_EOL;
        }
        return $res;
    }

    function fromList($url,$type) {
        $list = get_web_page($url);
        $result = [];
        if (preg_match_all('/^(\d[\d.]+):(\d+)\b/m', $list, $matches)) {
            foreach ($matches[0] as $ipport) {
                $result[] = $type . '://' . $ipport;
            }
        }
        return $result;

    }


    function get_web_page($url, $timeout = 30, $proxy = false) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, "");
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    # required for https urls
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;

        return $content;
    }
