<?php

function compute_hash($data) {
    return $data ? sha1(json_encode($data)) : null;
}

function update_readme($categories) {
    $readme = fopen("README.md", "w");
    fwrite($readme, "# ProcessMaker PM Blocks\nExplore our ready-to-go PM Blocks to kick-start your automation. Deploy these utilities into your Platform instance to power up your processes!");
    ksort($categories);  // Sort categories alphabetically
    foreach ($categories as $category => $blocks) {
        $category = str_replace("-", " ", $category);
        $category = ucwords($category);
        fwrite($readme, "\n## $category\n");
        usort($blocks, function($a, $b) { return strcmp($a['name'], $b['name']); });  // Sort blocks alphabetically within each category
        foreach ($blocks as $block) {
            fwrite($readme, "- **[{$block['name']}](/{$block['relative_path']})**: {$block['description']}\n");
        }
    }
    fclose($readme);
}

function main() {
    $root_dir = ".";
    $categories = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_dir));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) != "json") continue;
        if ($file->getFilename() == "index.json") continue;

        $filepath = $file->getPathname();
        $mod_time = date("Y-m-d H:i:s", filemtime($filepath));
        $data = json_decode(file_get_contents($filepath), true);
        $category = str_replace("./", "", $file->getPath());

        $block_info = [
            "name" => $data["name"],
            "description" => $data["export"][$data["root"]]["description"],
            "hash" => compute_hash($data["export"][$data["root"]]["attributes"]),
            "mod_time" => $mod_time,
            "relative_path" => $filepath,
            "uuid" => $data["root"],
        ];

        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }

        $categories[$category][] = $block_info;
    }

    ksort($categories);  // Sort categories alphabetically
    foreach ($categories as $category => $blocks) {
        usort($blocks, function($a, $b) { return strcmp($a['name'], $b['name']); });  // Sort blocks alphabetically within each category
        $categories[$category] = $blocks;
    }

    file_put_contents("index.json", json_encode($categories, JSON_PRETTY_PRINT));

    update_readme($categories);
}

main();
