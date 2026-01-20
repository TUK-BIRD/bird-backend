<!DOCTYPE html>
<html>
<body>
<h2>{{ $inviterName }}님이 당신을 [{{ $spaceName }}] 공간으로 초대했습니다.</h2>
<p>아래 버튼을 클릭하여 초대를 수락하세요.</p>
<a href="{{ $url }}"
   style="padding: 10px 20px; background: #228be6; color: white; text-decoration: none; border-radius: 5px;">
    초대 수락하기
</a>
<p>이 링크는 3일 동안 유효합니다.</p>
</body>
</html>


