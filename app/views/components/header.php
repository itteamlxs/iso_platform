<!-- Usuario -->
<div class="flex items-center space-x-3">
    <div class="hidden md:block text-right">
        <p class="text-sm font-medium text-gray-700"><?php echo $usuario_nombre; ?></p>
        <p class="text-xs text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $usuario_rol)); ?></p>
    </div>
    <div class="relative group">
        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm cursor-pointer">
            <?php echo $usuario_iniciales; ?>
        </div>
        <!-- Dropdown menu -->
        <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
            <a href="<?php echo BASE_URL; ?>/public/logout" 
               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
            </a>
        </div>
    </div>
</div>