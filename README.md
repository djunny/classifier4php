### classifier4php

基于 PHP 和 word2vec 的简单分类器，用于文章、新闻等内容自动分类，项目包含样本训练、识别代码，

分词组件用的是 PhpAnalysis，简单灵活。欢迎大家一起优化并完善。 

### 背景

每个搜索引擎其实都有一套完善的分类器，拿最简单的分类器举例，
不管你是巨头门户还是垂直三、四级以下的网站，他都能识别你的站点类型。
面向海量内容的今天，随随便便就能从互联网采集、抓取海量的数据，
而数据又杂乱无章，如果用人工整理归类，太浪费资源了。

作者做过各类站群、垂直站点，深知分类器的重要性。

### 运行环境

1. 操作系统：windows \ *inux
2. PHP 版本：PHP 5+
3. PHP 依赖：PHP-mbstring.
4. word2vec：window xp

如果您的操作系统是Linux、Centos等，

您需要自行下载 word2vec ( https://code.google.com/p/word2vec/ )编译。

然后修改 run.php 中 word2vec 执行路径:

```
define('EXE_WORD2VEC', 'word2vec.exe');
```

系统自带了基于 windows 的 word2vec 版本。


### 项目实例

项目中写了一个将小说自动训练并归类为：现代和古代的例子。

训练集结果文件已经存在于 source_data 目录中。

您可以直接将要识别的小说文件放至 source_target 中，即可自动识别。

### 运行方式

配置 PHP 路径到系统环境变量 PATH 中，或者手工执行：

/path/php run.php > run.log

即可在 run.log 中看到运行结果。

注：windows 下，设置好 PATH 后，也可以直接运行 run.bat