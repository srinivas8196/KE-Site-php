<?php
// Add Supabase client-side library
?>
<script src="https://unpkg.com/@supabase/supabase-js"></script>
<script>
const supabaseUrl = '<?php echo $_ENV['SUPABASE_URL']; ?>';
const supabaseKey = '<?php echo $_ENV['SUPABASE_KEY']; ?>';
const supabase = supabase.createClient(supabaseUrl, supabaseKey);
</script>

<footer class="bg-gray-900 text-white text-center p-4 mt-10 fixed bottom-0 w-full">
    <p class="text-sm">&copy; <?php echo date("Y"); ?> Karma Experience. All Rights Reserved.</p>
</footer>
