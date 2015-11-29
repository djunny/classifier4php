<?php
/**
 * User: djunny
 * Date: 2015/11/29
 * Time: 15:41
 */

error_reporting(E_ERROR);
// ���� word2vec ·��
define('EXE_WORD2VEC', 'word2vec.exe');

// �������
include 'include/phpanalysis.class.php';
include 'include/encoding.class.php';
include 'include/func.php';

// ��ʼ��������ȡѵ����
train();

/**
 * ѵ�����Ժ�������Ҫ��ѵ���õĽ�����ࡣ
 *
 * ���磬����ѵ����С˵�Ŵ����ִ��Ľ������
 *
 * source/�Ŵ�.txt
 * source/�ִ�.txt
 *
 * ��ô������ ��"С˵���" ��Ϊ age���� source_data Ŀ¼
 *
 * ����һ�� age Ŀ¼��
 *
 * Ȼ��ѹŴ�.txt �� �ִ�.txt ���� �ļ������� age Ŀ¼
 *
 * ��ʱ������Կ�ʼ����ʶ����
 */

// ��ʼ�� source_data ��ȡѵ������� source_target Ŀ¼��ȡҪʶ�������
analysis('source_target/', 'source_data/');

/**
 * ������������Ŀ¼��ÿһ������Ϊһ����Ŀ¼
 * �����Ҫ����ѵ������ɾ�� source ��Ŀ¼�� �� log �� txt
 *
 * @param string $source_dir
 */
function train($source_dir = 'source/') {
    //
    $cmd_exe = EXE_WORD2VEC;

    // ����һЩû���õĴ�Ƶ
    $unuse_data = file('unuse.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $unuse_data = array_flip($unuse_data);

    // ����Ŀ¼������Ŀ¼
    $dirs = array_diff(scandir($source_dir), array('..', '.'));
    foreach ($dirs as $dir) {
        $type_dir = $source_dir . $dir . '/';
        if (!is_dir($type_dir)) {
            continue;
        }
        // ������ǰĿ¼�������ļ�
        $files = array_diff(scandir($type_dir), array('..', '.'));
        $source_file = $source_dir . '_' . $dir . '.log';
        // ���ѵ�� log �Ѿ����ڣ���ô�˳�
        if (!$files || is_file($source_file)) {
            l('����ѵ��, Log�ļ�����', $dir);
            continue;
        }
        // �����ʷ�ļ�
        file_put_contents($source_file, '');
        foreach ($files as $file) {
            // ѵ�����Ľ�������ļ�
            $out_file = $source_dir . $dir . '.txt';
            $out = $return = '';
            if (is_file($out_file)) {
                l('����ѵ��, ����ļ�����', $dir);
                continue;
            }
            // �򿪵�ǰ�ļ�
            $lines = file($type_dir . $file);
            // �����ظ���
            $lines = array_flip(array_flip($lines));
            $count = count($lines);
            $content = '';
            // ********* �����Ӿ���ҵ����� ********
            // ȡǰ 1000��
            for ($i = 0; $i < min(1000, $count); $i++) {
                $content .= $lines[$i];
            }
            // ת utf8 ����
            $content = encoding::iconv($content);
            // �ִʣ����ݴ���ȡ�ؽ��
            $tags = get_trans_data($content);
            // �������ô�
            foreach ($tags as $index => $tag) {
                if (isset($unuse_data[$tag])) {
                    unset($tags[$index]);
                }
            }
            // ����
            if ($tags) {
                $fp = fopen($source_file, 'a+');
                fwrite($fp, implode(" ", $tags) . " ");
                fclose($fp);
            }
        }

        // ���� word2vec ������
        $run_cmd = "%exe% -train %in% -output %out% -cbow 0 -size 200 -window 5 -negative 0 -hs 1 -sample 1e-3 -threads 16 -classes 500";
        $run_cmd = str_replace('%exe%', $cmd_exe, $run_cmd);
        $run_cmd = str_replace('%out%', $out_file, $run_cmd);
        $run_cmd = str_replace('%in%', $source_file, $run_cmd);

        exec($run_cmd, $out, $return);

        // �򿪷������ļ�
        $content = file_get_contents($out_file);
        $content = explode("\n", $content);
        $arr = array();
        // ������һ��
        for ($i = 1, $l = count($content); $i < $l; $i++) {
            $line = explode(" ", trim($content[$i]));
            if (!$line[0]) {
                continue;
            }
            $arr[$line[0]] = $line[1];
        }
        // ������ �Լ� �򵥼���ÿ���ʵķ���
        $arr = array_keys($arr);
        $cnt = 10000;
        $num = 0;
        $new_arr = array();
        foreach ($arr as $v) {
            if (isset($tag_set[$v])) {
                continue;
            }
            $score = round(max($cnt - $num * 1.3, 5), 3);
            $new_arr[] = $v . ' ' . $score . ' ';
            $num++;
            //��������
            if ($num > $cnt) {
                break;
            }
        }

        // ���ʺͷ���д�����ļ�
        $fp = fopen($out_file, 'w+');
        fputs($fp, trim(implode("", $new_arr)));
        fclose($fp);
        unset($arr, $new_arr, $content);

    }
}

/**
 *
 * �������
 *
 * @param string $source_target Ŀ��Ҫʶ�������
 * @param string $source_data   ���ѵ��������Ŀ¼(ÿ����Ŀ¼һ������,��Ŀ¼����Ҫ���ؽ���±�)
 */
function analysis($source_target = 'source_target/', $source_data = 'source_data/') {
    // ȡ����Ҫ���ص�ѵ�����ļ�
    $cate_types = array_diff(scandir($source_data), array('..', '.'));
    $cate_datas = array();

    foreach ($cate_types as $cate_type) {
        // ���������ÿһ��ѵ����
        $source_path = $source_data . $cate_type . '/';
        $cate_files = array_diff(scandir($source_path), array('..', '.'));
        $cate_count = 0;
        foreach ($cate_files as $data_file) {
            $cate = str_replace('.txt', '', encoding::iconv($data_file));
            l('LoadSourceData', $cate_type, $cate);
            $datas = file_get_contents($source_path . $data_file);
            $tag_data = array();
            $tags = explode(' ', trim($datas));
            for ($j = 0, $k = count($tags); $j < $k; $j += 2) {
                $tag_data[$tags[$j]] = $tags[$j + 1];
            }
            $cate_datas[$cate_type][$cate] = $tag_data;
            $cate_count++;
        }
    }
    // ��ȡ������Ҫѵ��������
    $target_files = array_diff(scandir($source_target), array('..', '.'));
    foreach ($target_files as $target) {
        $lines = file($source_target . $target);
        $lines = array_flip(array_flip($lines));
        $count = count($lines);
        $content = '';

        // ********* �����Ӿ���ҵ����������ǵ�ǰʶ�����С˵���ִ�������ȡǰ��500����ʶ�𣬹��� ********
        $min_len = 500;
        for ($i = 0; $i < min($min_len, $count); $i++) {
            $content .= encoding::iconv($lines[$i]);
        }

        if ($count - 1 > $i) {
            for ($i = $count - 1, $l = max($min_len, $count - $min_len); $i >= $l; $i--) {
                $content .= encoding::iconv($lines[$i]);
            }
        }

        // ���˴��ԣ��õ��Ǹɴ�
        $tags = get_trans_data($content);

        // �򵥵ļ������
        $match_cates = array();
        foreach ($cate_datas as $cate_type => $trains_datas) {
            $max_score = 0;
            $max_cate = '';
            foreach ($trains_datas as $cate => $tag_data) {
                $score = 0;
                foreach ($tags as $tag) {
                    $score += $tag_data[$tag];
                }
                if ($score > $max_score) {
                    $max_score = $score;
                    $max_cate = $cate;
                }
                //l('Scan', $utf8_file, $cate, $score);
            }
            $match_cates[$cate_type] = $max_cate;
        }
        // ��ӡ�����
        l('Match', $match_cates, $target);
    }

}
