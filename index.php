<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="static/css/style.css">
    <title>Document</title>
</head>
<body>
    <aside class="sidebar" aria-label="Sidebar principal">
            <div>
                <nav>
                    <ul>
                        <li>
                            <a href="index.php">
                                <img src="static/images/house.png" alt="" srcset="">
                                <span>Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="templates/leitor.php">
                                <img src="static/images/videocam.png'" alt="" srcset="">
                                <span>Leitura e tradução</span>
                            </a>
                        </li>
                        <li>
                            <a href="templates/fotovideo.php">
                                <img src="static/images/image (1).png" alt="" srcset="">
                                <span>Tradução foto/video</span>
                            </a>
                        </li>
                        <li>
                            <a href="templates/administrador.php">
                                <img src="static/images/folder.png">
                                <span>Administrador</span>
                            </a>
                        </li>
                        <!-- Ajuda removido do menu principal conforme solicitado; permanece no menu hambúrguer -->
                    </ul>
                </nav>
            </div>
            <div>
                <div class="sidebar-card" role="button" aria-label="Menu rápido" id="hamburgerMenu">
                    <div style="display:flex;flex-direction:column;justify-content:center">
                      <img src="static/images/information.png" alt="" srcset="">
                    </div>
                </div>
                <div class="sidebar-menu" id="sidebarMenu" style="display:none;">
                    <ul>
                        <li><a href="templates/ajuda.php">Ajuda</a></li>
                    </ul>
                </div>
            </div>
        </aside>
</body>
</html>