<?php

namespace Lake\FormMedia\Http\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Support\Str;
use LakeFormMedia;
use Lake\FormMedia\MediaManager;

class FormMedia extends Controller
{
    /**
     * 获取文件列表
     */
    public function getFiles()
    {
        $path = request()->input('path', '/');

        $currentPage = (int)request()->input('page', 1);
        $perPage     = (int)request()->input('pageSize', 120);

        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($path);

        // 驱动磁盘
        $disk = request()->input('disk', '');
        if (!empty($disk)) {
            $manager = $manager->withDisk($disk);
        }

        $type  = (string)request()->input('type', 'image');
        $order = (string)request()->input('order', 'time');

        $files = $manager->ls($type, $order);
        $list  = collect($files)
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();

        $totalPage = count(collect($files)->chunk($perPage));

        $data = [
            'list'         => $list, // 数据
            'total_page'   => $totalPage, // 数量
            'current_page' => $currentPage, // 当前页码
            'per_page'     => $perPage, // 每页数量
            'nav'          => $manager->navigation()  // 导航
        ];

        return $this->renderJson(LakeFormMedia::trans('form-media.get_success'), 200, $data);
    }

    /**
     * 上传
     */
    public function upload()
    {
        $files = request()->file('files');
        $path  = request()->get('path', '/');

        $type     = request()->get('type');
        $nametype = request()->get('nametype', 'uniqid');

        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($path)
            ->setNametype($nametype);

        // 驱动磁盘
        $disk = request()->input('disk', '');
        if (!empty($disk)) {
            $manager = $manager->withDisk($disk);
        }

        if ($type != 'blend') {
            if (!$manager->checkType($files, $type)) {
                return $this->renderJson(LakeFormMedia::trans('form-media.upload_file_ext_error'), -1);
            }
        }

        try {
            if ($manager->upload($files)) {
                return $this->renderJson(LakeFormMedia::trans('form-media.upload_success'), 200);
            }
        } catch (\Exception $e) {
        }

        return $this->renderJson(LakeFormMedia::trans('form-media.upload_error'), -1);
    }

    /**
     * 新建文件夹
     */
    public function createFolder()
    {
        $dir  = request()->input('dir');
        $name = request()->input('name');

        if (empty($dir)) {
            return $this->renderJson(LakeFormMedia::trans('form-media.create_dirname_empty'), -1);
        }

        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($dir);

        // 驱动磁盘
        $disk = request()->input('disk', '');
        if (!empty($disk)) {
            $manager = $manager->withDisk($disk);
        }

        try {
            if ($manager->createFolder($name)) {
                return $this->renderJson(LakeFormMedia::trans('form-media.create_success'), 200);
            }
        } catch (\Exception $e) {
        }

        return $this->renderJson(LakeFormMedia::trans('form-media.create_error'), -1);
    }

    /**
     * 删除文件
     */
    protected function delete()
    {
        $files = request()->input("files");
        $files = explode(",", $files);
        foreach ($files as $file) {
            try {
                if (Str::startsWith($file, "/")) {
                    $file = Str::replaceFirst("/", "", $file);
                }
                if (is_dir(storage_path("app/public/" . $file))) {
                    deldir(storage_path("app/public/" . $file) . "/", true);
                } else {
                    unlink(storage_path("app/public/" . $file));
                }
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
        return $this->renderJson("删除成功", 200);
    }

    /**
     * 输出json
     */
    protected function renderJson($msg, $code = 200, $data = [])
    {
        return response()->json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }

}



